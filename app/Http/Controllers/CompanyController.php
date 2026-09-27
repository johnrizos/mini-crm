<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Http\Requests\Crm\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\DealResource;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $companies = $this->user()->companies()
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('domain', 'like', $like));
            })
            ->withCount('contacts')
            ->withSum(
                ['deals as open_deals_value_cents' => fn (Builder $q) => $q->whereIn('stage', DealStage::open())],
                'value_cents',
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('companies/Index', [
            'companies' => CompanyResource::collection($companies),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('companies/Create');
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $company = $this->user()->companies()->create($request->validated());
        $this->toast("{$company->name} added.");

        return to_route('companies.show', $company);
    }

    public function show(Company $company): Response
    {
        Gate::authorize('view', $company);

        $company->loadCount('contacts');

        return Inertia::render('companies/Show', [
            'company' => new CompanyResource($company),
            'contacts' => ContactResource::collection($company->contacts()->orderBy('last_name')->get()),
            'deals' => DealResource::collection($company->deals()->with('contact')->latest()->get()),
        ]);
    }

    public function edit(Company $company): Response
    {
        Gate::authorize('update', $company);

        return Inertia::render('companies/Edit', [
            'company' => new CompanyResource($company),
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        Gate::authorize('update', $company);

        $company->update($request->validated());
        $this->toast('Company updated.');

        return to_route('companies.show', $company);
    }

    public function destroy(Company $company): RedirectResponse
    {
        Gate::authorize('delete', $company);

        $company->delete();
        $this->toast("{$company->name} deleted. Its contacts were kept.");

        return to_route('companies.index');
    }
}
