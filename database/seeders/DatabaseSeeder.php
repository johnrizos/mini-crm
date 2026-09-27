<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A demo account with enough data to make every screen meaningful.
     * Log in as demo@example.com / password.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $companies = Company::factory(12)->for($user)->create();

        $contacts = Contact::factory(60)
            ->for($user)
            ->state(fn () => ['company_id' => fake()->boolean(85) ? $companies->random()->id : null])
            ->create();

        $titles = ['Annual license', 'Onboarding package', 'Newsletter redesign', 'CRM migration', 'Q4 campaign', 'Support plan', 'Analytics setup'];
        $stages = [
            DealStage::New, DealStage::New, DealStage::Qualified, DealStage::Qualified,
            DealStage::Proposal, DealStage::Proposal, DealStage::Negotiation, DealStage::Won, DealStage::Lost,
        ];

        $positions = [];
        foreach (range(1, 28) as $i) {
            /** @var DealStage $stage */
            $stage = fake()->randomElement($stages);
            /** @var Contact $contact */
            $contact = $contacts->random();
            $positions[$stage->value] = ($positions[$stage->value] ?? -1) + 1;

            Deal::factory()->for($user)->create([
                'contact_id' => $contact->id,
                'company_id' => $contact->company_id,
                'title' => fake()->randomElement($titles),
                'stage' => $stage,
                'position' => $positions[$stage->value],
                'closed_at' => $stage->isClosed() ? fake()->dateTimeBetween('-60 days') : null,
                'expected_close_date' => $stage->isClosed() ? null : fake()->dateTimeBetween('now', '+45 days'),
            ]);
        }

        $notes = [
            ActivityType::Call->value => ['Intro call, interested in the Q4 plan.', 'Walked through pricing. Wants a proposal by Friday.', 'Left a voicemail.'],
            ActivityType::Email->value => ['Sent the case study and pricing sheet.', 'Followed up on the proposal.', 'Shared the onboarding checklist.'],
            ActivityType::Meeting->value => ['Demo with the marketing team.', 'Kick-off meeting, agreed on the timeline.', 'Quarterly review.'],
            ActivityType::Note->value => ['Budget approved for next quarter.', 'Decision maker is the CFO.', 'Prefers phone over email.'],
        ];

        foreach ($contacts->random(40) as $contact) {
            foreach (range(1, fake()->numberBetween(1, 4)) as $n) {
                /** @var ActivityType $type */
                $type = fake()->randomElement(ActivityType::cases());

                Activity::factory()->create([
                    'contact_id' => $contact->id,
                    'user_id' => $user->id,
                    'type' => $type,
                    'body' => fake()->randomElement($notes[$type->value]),
                ]);
            }
        }

        // Whoever signed a deal is a customer now.
        Contact::query()
            ->whereIn('id', Deal::query()->where('stage', DealStage::Won)->whereNotNull('contact_id')->pluck('contact_id'))
            ->update(['status' => ContactStatus::Customer]);
    }
}
