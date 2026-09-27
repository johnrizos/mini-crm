<?php

namespace Tests\Feature\Crm;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('companies.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_the_users_companies_with_counts(): void
    {
        $user = User::factory()->create();
        $acme = Company::factory()->for($user)->create(['name' => 'Acme']);
        Contact::factory(2)->for($user)->create(['company_id' => $acme->id]);
        Deal::factory()->for($user)->create(['company_id' => $acme->id, 'value_cents' => 1000_00]);
        Deal::factory()->for($user)->stage(DealStage::Won)->create(['company_id' => $acme->id, 'value_cents' => 5000_00]);
        Company::factory()->create(['name' => 'Someone Else Ltd']);

        $this->actingAs($user)
            ->get(route('companies.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/Index')
                ->has('companies.data', 1)
                ->where('companies.data.0.name', 'Acme')
                ->where('companies.data.0.contacts_count', 2)
                // Won deals are not part of the open pipeline.
                ->where('companies.data.0.open_deals_value_cents', 1000_00));
    }

    public function test_index_searches_by_name_and_domain(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create(['name' => 'Acme', 'domain' => 'acme.test']);
        Company::factory()->for($user)->create(['name' => 'Globex', 'domain' => 'globex.test']);

        $this->actingAs($user)
            ->get(route('companies.index', ['search' => 'globex.t']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('companies.data', 1)
                ->where('companies.data.0.name', 'Globex'));
    }

    public function test_store_creates_a_company_for_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('companies.store'), [
            'name' => 'Acme',
            'domain' => 'acme.test',
            'industry' => 'Retail',
        ]);

        $company = Company::query()->sole();
        $response->assertRedirect(route('companies.show', $company));
        $this->assertSame($user->id, $company->user_id);
    }

    public function test_names_are_unique_per_user_only(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create(['name' => 'Acme']);
        Company::factory()->create(['name' => 'Taken Elsewhere']);

        $this->actingAs($user)
            ->post(route('companies.store'), ['name' => 'Acme'])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->post(route('companies.store'), ['name' => 'Taken Elsewhere'])
            ->assertSessionHasNoErrors();
    }

    public function test_domain_must_not_include_the_protocol(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('companies.store'), ['name' => 'Acme', 'domain' => 'https://acme.test'])
            ->assertSessionHasErrors('domain');
    }

    public function test_update_keeps_its_own_name_valid(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create(['name' => 'Acme']);

        $this->actingAs($user)
            ->put(route('companies.update', $company), ['name' => 'Acme', 'industry' => 'SaaS'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.show', $company));

        $this->assertSame('SaaS', $company->fresh()?->industry);
    }

    public function test_deleting_a_company_keeps_its_contacts(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $contact = Contact::factory()->for($user)->create(['company_id' => $company->id]);

        $this->actingAs($user)->delete(route('companies.destroy', $company))->assertRedirect(route('companies.index'));

        $this->assertModelMissing($company);
        $this->assertNull($contact->fresh()?->company_id);
    }

    public function test_other_users_companies_are_not_found(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('companies.show', $company))->assertNotFound();
        $this->get(route('companies.edit', $company))->assertNotFound();
        $this->put(route('companies.update', $company), ['name' => 'Hijacked'])->assertNotFound();
        $this->delete(route('companies.destroy', $company))->assertNotFound();

        $this->assertModelExists($company);
    }
}
