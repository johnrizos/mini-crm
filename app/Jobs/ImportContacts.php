<?php

namespace App\Jobs;

use App\Enums\ContactStatus;
use App\Enums\ImportStatus;
use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Imports contacts from an uploaded CSV. Bad rows are reported with their line
 * number and skipped; they never stop the rest of the file.
 */
class ImportContacts implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public const MAX_ROWS = 5000;

    private const MAX_ERRORS = 100;

    private const REQUIRED_COLUMNS = ['first_name', 'last_name', 'email'];

    /** Common header spellings mapped to our column names. */
    private const ALIASES = [
        'firstname' => 'first_name',
        'given_name' => 'first_name',
        'lastname' => 'last_name',
        'surname' => 'last_name',
        'family_name' => 'last_name',
        'e_mail' => 'email',
        'email_address' => 'email',
        'phone_number' => 'phone',
        'mobile' => 'phone',
        'title' => 'job_title',
        'position' => 'job_title',
        'company_name' => 'company',
        'organization' => 'company',
    ];

    /** @var list<array{row: int, messages: list<string>}> */
    private array $errors = [];

    public function __construct(public ContactImport $import) {}

    public function handle(): void
    {
        $import = $this->import;
        $import->loadMissing('user');
        $import->update(['status' => ImportStatus::Processing]);

        try {
            $this->run($import, $import->user);
        } catch (RuntimeException $e) {
            $this->addError(0, $e->getMessage());
            $import->status = ImportStatus::Failed;
        } finally {
            Storage::disk('local')->delete($import->path);
        }

        $import->errors = $this->errors ?: null;
        $import->save();
    }

    public function failed(?Throwable $e): void
    {
        $this->import->update([
            'status' => ImportStatus::Failed,
            'errors' => [['row' => 0, 'messages' => ['The import stopped unexpectedly. Please try again.']]],
        ]);
    }

    private function run(ContactImport $import, User $user): void
    {
        $file = new SplFileObject(Storage::disk('local')->path($import->path));
        $firstLine = (string) $file->fgets();
        $file->rewind();

        // Excel in most of Europe saves "CSV" with semicolons.
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $file->setCsvControl($delimiter, '"', '');

        $header = $this->header($file->current());
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            throw new RuntimeException('Missing required column(s): '.implode(', ', $missing).'.');
        }

        $existing = array_flip($user->contacts()->pluck('email')->map(fn ($e) => strtolower((string) $e))->all());
        $companies = $user->companies()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower((string) $name) => (int) $id])->all();
        $total = $imported = $skipped = 0;

        $file->next();
        while ($file->valid()) {
            $line = $file->key() + 1;
            $cells = $file->current();
            $file->next();

            if (! is_array($cells) || $cells === [null]) {
                continue;
            }

            if (++$total > self::MAX_ROWS) {
                $total--;
                $this->addError($line, 'Only the first '.self::MAX_ROWS.' rows are imported.');
                break;
            }

            $row = $this->combine($header, $cells);
            $validator = Validator::make($row, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'job_title' => ['nullable', 'string', 'max:100'],
                'company' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', new Enum(ContactStatus::class)],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $this->addError($line, ...$validator->errors()->all());

                continue;
            }

            $email = strtolower((string) $row['email']);
            if (isset($existing[$email])) {
                $skipped++;
                $this->addError($line, "{$email} already exists, skipped.");

                continue;
            }

            $companyId = null;
            if (($name = $row['company'] ?? null) !== null) {
                $companyId = $companies[mb_strtolower($name)] ??= $user->companies()->create(['name' => $name])->id;
            }

            $user->contacts()->create([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $email,
                'phone' => $row['phone'] ?? null,
                'job_title' => $row['job_title'] ?? null,
                'status' => $row['status'] ?? ContactStatus::Lead->value,
                'company_id' => $companyId,
            ]);

            $existing[$email] = true;
            $imported++;
        }

        $import->fill([
            'status' => ImportStatus::Completed,
            'total_rows' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return list<string>
     */
    private function header(mixed $cells): array
    {
        if (! is_array($cells) || $cells === [null]) {
            throw new RuntimeException('The file is empty.');
        }

        return array_map(function ($cell) {
            $name = strtolower(trim((string) $cell, " \t\n\r\0\x0B\u{FEFF}"));
            $name = (string) preg_replace('/[\s\-]+/', '_', $name);

            return self::ALIASES[$name] ?? $name;
        }, array_values($cells));
    }

    /**
     * @param  list<string>  $header
     * @param  array<int, mixed>  $cells
     * @return array<string, string|null>
     */
    private function combine(array $header, array $cells): array
    {
        $row = [];
        foreach ($header as $i => $column) {
            $value = trim((string) ($cells[$i] ?? ''));
            $row[$column] = $value === '' ? null : $value;
        }

        return $row;
    }

    private function addError(int $row, string ...$messages): void
    {
        if (count($this->errors) < self::MAX_ERRORS) {
            $this->errors[] = ['row' => $row, 'messages' => array_values($messages)];
        }
    }
}
