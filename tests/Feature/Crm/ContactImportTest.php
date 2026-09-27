<?php

namespace Tests\Feature\Crm;

use App\Enums\ContactStatus;
use App\Enums\ImportStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The queue runs synchronously in tests, so the job has finished by the time
 * the upload request returns.
 */
class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
    }

    public function test_imports_valid_rows_and_reports_the_rest(): void
    {
        Contact::factory()->for($this->user)->create(['email' => 'taken@acme.test']);
        Company::factory()->for($this->user)->create(['name' => 'Acme']);

        $import = $this->upload(implode("\n", [
            'First Name,Last Name,Email,Company,Status',
            'Maria,Papadopoulou,Maria@Acme.test,acme,customer',
            'Nikos,Georgiou,nikos@newco.test,NewCo,',
            'Bad,Email,not-an-email,,',
            'Dup,Existing,taken@acme.test,,',
            'Dup,InFile,maria@acme.test,,',
            ',NoFirstName,x@y.test,,',
        ]));

        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame([6, 2, 4], [$import->total_rows, $import->imported, $import->skipped]);
        $this->assertSame([4, 5, 6, 7], array_column($import->errors ?? [], 'row'));

        $maria = Contact::query()->where('email', 'maria@acme.test')->sole();
        $this->assertSame(ContactStatus::Customer, $maria->status);
        // Matched the existing company case-insensitively instead of creating a duplicate.
        $this->assertSame('Acme', $maria->company?->name);

        $nikos = Contact::query()->where('email', 'nikos@newco.test')->sole();
        $this->assertSame(ContactStatus::Lead, $nikos->status);
        $this->assertSame('NewCo', $nikos->company?->name);
        $this->assertSame(2, Company::query()->count());
    }

    public function test_handles_excel_style_files(): void
    {
        // BOM, semicolons and alternative header names, as Excel exports them.
        $import = $this->upload("\u{FEFF}firstname;surname;E-mail;Mobile\nMaria;Papadopoulou;maria@acme.test;+30 210 000 0000\n");

        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame(1, $import->imported);
        $this->assertSame('+30 210 000 0000', Contact::query()->sole()->phone);
    }

    public function test_fails_when_required_columns_are_missing(): void
    {
        $import = $this->upload("name,email\nMaria,maria@acme.test\n");

        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertStringContainsString('first_name, last_name', $import->errors[0]['messages'][0] ?? '');
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_the_uploaded_file_is_removed_afterwards(): void
    {
        $import = $this->upload("first_name,last_name,email\nMaria,P,maria@acme.test\n");

        Storage::disk('local')->assertMissing($import->path);
    }

    public function test_rejects_files_that_are_not_csv(): void
    {
        $this->actingAs($this->user)
            ->post(route('contacts.import.store'), ['file' => UploadedFile::fake()->create('photo.png', 10, 'image/png')])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('contact_imports', 0);
    }

    public function test_import_page_lists_only_the_users_imports(): void
    {
        $this->upload("first_name,last_name,email\nMaria,P,maria@acme.test\n");
        $this->upload("first_name,last_name,email\nMaria,P,maria@other.test\n", User::factory()->create());

        $this->actingAs($this->user)
            ->get(route('contacts.import.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/Import')
                ->has('imports', 1)
                ->where('imports.0.status', 'completed'));
    }

    private function upload(string $csv, ?User $as = null): ContactImport
    {
        $as ??= $this->user;

        $this->actingAs($as)
            ->post(route('contacts.import.store'), ['file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv)])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contacts.import.create'));

        return $as->contactImports()->latest('id')->firstOrFail();
    }
}
