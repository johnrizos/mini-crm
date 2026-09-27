<?php

namespace Tests\Feature\Crm;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_an_activity_on_a_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->for($user)->create();
        $deal = Deal::factory()->for($user)->create(['contact_id' => $contact->id]);

        $this->freezeSecond();
        $this->actingAs($user)
            ->post(route('contacts.activities.store', $contact), ['type' => 'call', 'body' => 'Discussed pricing.', 'deal_id' => $deal->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $activity = Activity::query()->sole();
        $this->assertSame(ActivityType::Call, $activity->type);
        $this->assertSame($user->id, $activity->user_id);
        $this->assertSame($deal->id, $activity->deal_id);
        $this->assertTrue($activity->happened_at->equalTo(now()));
    }

    public function test_activities_cannot_be_logged_in_the_future_or_against_foreign_deals(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->for($user)->create();
        $foreignDeal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'note',
                'body' => 'x',
                'happened_at' => now()->addDay()->toDateTimeString(),
                'deal_id' => $foreignDeal->id,
            ])
            ->assertSessionHasErrors(['happened_at', 'deal_id']);
    }

    public function test_other_users_contacts_and_activities_are_off_limits(): void
    {
        $user = User::factory()->create();
        $foreignContact = Contact::factory()->create();
        $foreignActivity = Activity::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.activities.store', $foreignContact), ['type' => 'note', 'body' => 'x'])
            ->assertNotFound();
        $this->actingAs($user)->delete(route('activities.destroy', $foreignActivity))->assertNotFound();

        $this->assertModelExists($foreignActivity);
    }

    public function test_deleting_an_activity(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($activity->user)->delete(route('activities.destroy', $activity))->assertRedirect();

        $this->assertModelMissing($activity);
    }
}
