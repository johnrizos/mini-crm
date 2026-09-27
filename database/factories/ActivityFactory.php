<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            // Always the contact's owner, so factories never mix accounts.
            'user_id' => fn (array $attributes) => Contact::query()->findOrFail((int) $attributes['contact_id'])->user_id,
            'deal_id' => null,
            'type' => fake()->randomElement(ActivityType::cases()),
            'body' => fake()->sentence(12),
            'happened_at' => fake()->dateTimeBetween('-2 months'),
        ];
    }
}
