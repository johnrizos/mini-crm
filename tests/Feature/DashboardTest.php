<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_summarizes_only_the_users_data()
    {
        $this->travelTo(now()->setDay(15));
        $user = User::factory()->create();

        Contact::factory(2)->for($user)->create(['status' => ContactStatus::Lead]);
        $customer = Contact::factory()->for($user)->create(['status' => ContactStatus::Customer]);
        Deal::factory()->for($user)->stage(DealStage::New)->create(['value_cents' => 100_00]);
        Deal::factory()->for($user)->stage(DealStage::Proposal)->create(['value_cents' => 250_00, 'expected_close_date' => now()->addDays(3)]);
        Deal::factory()->for($user)->stage(DealStage::Won)->create(['value_cents' => 900_00]);
        Deal::factory()->for($user)->stage(DealStage::Lost)->create(['value_cents' => 50_00]);
        Activity::factory()->create(['contact_id' => $customer->id]);

        // Someone else's data must not leak into the numbers.
        Deal::factory()->stage(DealStage::New)->create(['value_cents' => 99_999_00]);
        Contact::factory(5)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.contacts', 3)
                ->where('stats.open_deals', 2)
                ->where('stats.pipeline_cents', 350_00)
                ->where('stats.won_this_month_cents', 900_00)
                ->where('stats.win_rate', 50)
                ->where('pipeline.0.stage', 'new')
                ->where('pipeline.0.value_cents', 100_00)
                ->where('contactsByStatus.0.total', 2)
                ->has('closingSoon', 1)
                ->has('recentActivities', 1));
    }
}
