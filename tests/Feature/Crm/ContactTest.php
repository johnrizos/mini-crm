<?php

namespace Tests\Feature\Crm;

use App\Enums\ContactStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function searches(): array
    {
        return [
            'first name' => ['mari'],
            'last name' => ['papad'],
            'full name' => ['maria papa'],
            'email' => ['maria@acme'],
            'company' => ['acme'],
        ];
    }

    #[DataProvider('searches')]
    public function test_index_searches_names_email_and_company(string $term): void
    {
        $acme = Company::factory()->for($this->user)->create(['name' => 'Acme']);
        Contact::factory()->for($this->user)->create([
            'first_name' => 'Maria', 'last_name' => 'Papadopoulou', 'email' => 'maria@acme.test', 'company_id' => $acme->id,
        ]);
        Contact::factory()->for($this->user)->create([
            'first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john@globex.test',
        ]);

        $this->actingAs($this->user)
            ->get(route('contacts.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/Index')
                ->has('contacts.data', 1)
                ->where('contacts.data.0.full_name', 'Maria Papadopoulou')
                ->where('contacts.data.0.company.name', 'Acme'));
    }

    public function test_search_treats_wildcards_literally(): void
    {
        Contact::factory()->for($this->user)->create(['first_name' => 'Maria']);

        $this->actingAs($this->user)
            ->get(route('contacts.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('contacts.data', 0));
    }

    public function test_index_filters_by_status_and_hides_other_users(): void
    {
        Contact::factory()->for($this->user)->create(['status' => ContactStatus::Customer]);
        Contact::factory()->for($this->user)->create(['status' => ContactStatus::Lead]);
        Contact::factory()->create(['status' => ContactStatus::Customer]);

        $this->actingAs($this->user)
            ->get(route('contacts.index', ['status' => 'customer']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.status', 'customer')
                ->where('filters.status', 'customer'));
    }

    public function test_store_normalizes_the_email(): void
    {
        $this->actingAs($this->user)
            ->post(route('contacts.store'), $this->payload(['email' => '  Maria@Acme.TEST ']))
            ->assertSessionHasNoErrors();

        $this->assertSame('maria@acme.test', Contact::query()->sole()->email);
    }

    public function test_emails_are_unique_per_user(): void
    {
        Contact::factory()->for($this->user)->create(['email' => 'maria@acme.test']);
        Contact::factory()->create(['email' => 'other@acme.test']);

        $this->actingAs($this->user)
            ->post(route('contacts.store'), $this->payload(['email' => 'MARIA@acme.test']))
            ->assertSessionHasErrors('email');

        $this->actingAs($this->user)
            ->post(route('contacts.store'), $this->payload(['email' => 'other@acme.test']))
            ->assertSessionHasNoErrors();
    }

    public function test_a_contact_cannot_be_linked_to_another_users_company(): void
    {
        $foreign = Company::factory()->create();

        $this->actingAs($this->user)
            ->post(route('contacts.store'), $this->payload(['company_id' => $foreign->id]))
            ->assertSessionHasErrors('company_id');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_show_includes_the_activity_timeline_newest_first(): void
    {
        $contact = Contact::factory()->for($this->user)->create();
        Activity::factory()->create(['contact_id' => $contact->id, 'body' => 'older', 'happened_at' => now()->subDays(3)]);
        Activity::factory()->create(['contact_id' => $contact->id, 'body' => 'newer', 'happened_at' => now()->subDay()]);

        $this->actingAs($this->user)
            ->get(route('contacts.show', $contact))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/Show')
                ->where('contact.id', $contact->id)
                ->has('activities', 2)
                ->where('activities.0.body', 'newer'));
    }

    public function test_update_and_delete(): void
    {
        $contact = Contact::factory()->for($this->user)->create(['email' => 'maria@acme.test']);

        $this->actingAs($this->user)
            ->put(route('contacts.update', $contact), $this->payload(['email' => 'maria@acme.test', 'status' => 'customer']))
            ->assertRedirect(route('contacts.show', $contact));
        $this->assertSame(ContactStatus::Customer, $contact->fresh()?->status);

        $this->actingAs($this->user)->delete(route('contacts.destroy', $contact))->assertRedirect(route('contacts.index'));
        $this->assertModelMissing($contact);
    }

    public function test_other_users_contacts_are_not_found(): void
    {
        $contact = Contact::factory()->create();
        $this->actingAs($this->user);

        $this->get(route('contacts.show', $contact))->assertNotFound();
        $this->put(route('contacts.update', $contact), $this->payload())->assertNotFound();
        $this->delete(route('contacts.destroy', $contact))->assertNotFound();

        $this->assertModelExists($contact);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'email' => 'maria@acme.test',
            'status' => 'lead',
            ...$overrides,
        ];
    }
}
