<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Http\Requests\Crm\ContactImportRequest;
use App\Jobs\ImportContacts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class ContactImportController extends Controller
{
    public function create(): Response
    {
        $imports = $this->user()->contactImports()->latest()->limit(10)->get();

        return Inertia::render('contacts/Import', [
            'imports' => $imports->map(fn ($import) => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'imported' => $import->imported,
                'skipped' => $import->skipped,
                'errors' => $import->errors ?? [],
                'created_at' => $import->created_at?->toIso8601String(),
            ]),
            'maxRows' => ImportContacts::MAX_ROWS,
        ]);
    }

    public function store(ContactImportRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $user = $this->user();

        $import = $user->contactImports()->create([
            'filename' => $file->getClientOriginalName(),
            'path' => (string) $file->store("imports/{$user->id}", 'local'),
            'status' => ImportStatus::Pending,
        ]);

        ImportContacts::dispatch($import);

        $this->toast('Import started. This page updates when it finishes.');

        return to_route('contacts.import.create');
    }
}
