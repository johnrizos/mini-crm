<?php

namespace Tests\Feature\Crm;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_board_shows_open_deals_and_recent_closed_ones(): void
    {
        Deal::factory()->for($this->user)->create(['title' => 'Open']);
        Deal::factory()->for($this->user)->stage(DealStage::Won)->create(['title' => 'Won recently']);
        Deal::factory()->for($this->user)->create(['title' => 'Won long ago', 'stage' => DealStage::Won, 'closed_at' => now()->subYear()]);
        Deal::factory()->create(['title' => 'Not mine']);

        $this->actingAs($this->user)
            ->get(route('deals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/Index')
                ->has('deals', 2)
                ->where('deals.0.title', 'Open')
                ->where('deals.1.title', 'Won recently'));
    }

    public function test_store_converts_euros_to_cents_and_appends_to_the_stage(): void
    {
        Deal::factory()->for($this->user)->stage(DealStage::Proposal, 0)->create();
        Deal::factory()->for($this->user)->stage(DealStage::Proposal, 1)->create();

        $this->actingAs($this->user)
            ->post(route('deals.store'), ['title' => 'Annual plan', 'value' => '1234.56', 'stage' => 'proposal'])
            ->assertSessionHasNoErrors();

        $deal = Deal::query()->where('title', 'Annual plan')->sole();
        $this->assertSame(123456, $deal->value_cents);
        $this->assertSame(2, $deal->position);
        $this->assertNull($deal->closed_at);
    }

    public function test_deals_cannot_point_at_another_users_contact(): void
    {
        $foreign = Contact::factory()->create();

        $this->actingAs($this->user)
            ->post(route('deals.store'), ['title' => 'x', 'value' => 10, 'stage' => 'new', 'contact_id' => $foreign->id])
            ->assertSessionHasErrors('contact_id');
    }

    public function test_moving_within_a_stage_reorders_it(): void
    {
        [$a, $b, $c] = $this->column(DealStage::New, 3);

        $this->move($c, DealStage::New, 0);

        $this->assertOrder(DealStage::New, [$c, $a, $b]);
    }

    public function test_moving_across_stages_renumbers_both_and_closes_the_deal(): void
    {
        [$a, $b, $c] = $this->column(DealStage::Negotiation, 3);
        [$w1] = $this->column(DealStage::Won, 1);

        $this->move($b, DealStage::Won, 0);

        $this->assertOrder(DealStage::Negotiation, [$a, $c]);
        $this->assertOrder(DealStage::Won, [$b, $w1]);
        $this->assertNotNull($b->fresh()?->closed_at);
    }

    public function test_reopening_a_closed_deal_clears_closed_at(): void
    {
        [$won] = $this->column(DealStage::Won, 1);

        $this->move($won, DealStage::Negotiation, 5);

        $fresh = $won->fresh();
        $this->assertSame(DealStage::Negotiation, $fresh?->stage);
        $this->assertSame(0, $fresh->position);
        $this->assertNull($fresh->closed_at);
    }

    public function test_changing_the_stage_in_the_form_moves_the_deal_to_the_end(): void
    {
        [$a, $b] = $this->column(DealStage::New, 2);
        [$q] = $this->column(DealStage::Qualified, 1);

        $this->actingAs($this->user)
            ->put(route('deals.update', $a), ['title' => $a->title, 'value' => 10, 'stage' => 'qualified'])
            ->assertSessionHasNoErrors();

        $this->assertOrder(DealStage::New, [$b]);
        $this->assertOrder(DealStage::Qualified, [$q, $a]);
    }

    public function test_deleting_closes_the_gap(): void
    {
        [$a, $b, $c] = $this->column(DealStage::Proposal, 3);

        $this->actingAs($this->user)->delete(route('deals.destroy', $b))->assertRedirect();

        $this->assertOrder(DealStage::Proposal, [$a, $c]);
    }

    public function test_other_users_deals_are_not_found(): void
    {
        $deal = Deal::factory()->create();
        $this->actingAs($this->user);

        $this->patch(route('deals.move', $deal), ['stage' => 'won', 'position' => 0])->assertNotFound();
        $this->put(route('deals.update', $deal), ['title' => 'x', 'value' => 1, 'stage' => 'new'])->assertNotFound();
        $this->delete(route('deals.destroy', $deal))->assertNotFound();

        $this->assertSame(DealStage::New, $deal->fresh()?->stage);
    }

    /**
     * @return list<Deal>
     */
    private function column(DealStage $stage, int $count): array
    {
        $deals = [];
        for ($i = 0; $i < $count; $i++) {
            $deals[] = Deal::factory()->for($this->user)->stage($stage, $i)->create();
        }

        return $deals;
    }

    private function move(Deal $deal, DealStage $stage, int $position): void
    {
        $this->actingAs($this->user)
            ->patch(route('deals.move', $deal), ['stage' => $stage->value, 'position' => $position])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    /**
     * @param  list<Deal>  $expected
     */
    private function assertOrder(DealStage $stage, array $expected): void
    {
        $actual = Deal::query()->where('stage', $stage)->orderBy('position')->get(['id', 'position']);

        $this->assertSame(array_map(fn (Deal $d) => $d->id, $expected), $actual->pluck('id')->all());
        $this->assertSame(range(0, count($expected) - 1), $actual->pluck('position')->all());
    }
}
