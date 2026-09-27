<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\Crm\ContactRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\DealResource;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->enum('status', ContactStatus::class)?->value,
            'company' => $request->integer('company') ?: null,
            'sort' => $request->string('sort')->toString() === 'recent' ? 'recent' : 'name',
        ];

        $contacts = $this->user()->contacts()
            ->with('company')
            ->search($filters['search'])
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['company'], fn ($q, $company) => $q->where('company_id', $company))
            ->when(
                $filters['sort'] === 'recent',
                fn ($q) => $q->latest(),
                fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            )
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contacts/Index', [
            'contacts' => ContactResource::collection($contacts),
            'filters' => $filters,
            'companies' => $this->companyOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('contacts/Create', [
            'companies' => $this->companyOptions(),
            'companyId' => $request->integer('company') ?: null,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = $this->user()->contacts()->create($request->validated());
        $this->toast("{$contact->full_name} added.");

        return to_route('contacts.show', $contact);
    }

    public function show(Contact $contact): Response
    {
        Gate::authorize('view', $contact);

        $contact->load('company');

        return Inertia::render('contacts/Show', [
            'contact' => new ContactResource($contact),
            'deals' => DealResource::collection($contact->deals()->with('company')->latest()->get()),
            'activities' => ActivityResource::collection(
                $contact->activities()->with('deal')->latest('happened_at')->latest('id')->limit(50)->get(),
            ),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        Gate::authorize('update', $contact);

        return Inertia::render('contacts/Edit', [
            'contact' => new ContactResource($contact),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        Gate::authorize('update', $contact);

        $contact->update($request->validated());
        $this->toast('Contact updated.');

        return to_route('contacts.show', $contact);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('delete', $contact);

        $contact->delete();
        $this->toast("{$contact->full_name} deleted.");

        return to_route('contacts.index');
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function companyOptions(): Collection
    {
        return $this->user()->companies()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $c) => ['id' => $c->id, 'name' => $c->name]);
    }
}
