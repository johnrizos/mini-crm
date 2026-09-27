<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contact_id' => null,
            'company_id' => null,
            'title' => rtrim(fake()->sentence(3), '.'),
            'value_cents' => fake()->numberBetween(5, 500) * 100_00,
            'stage' => DealStage::New,
            'position' => 0,
            'expected_close_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'closed_at' => null,
        ];
    }

    public function stage(DealStage $stage, int $position = 0): static
    {
        return $this->state(fn () => [
            'stage' => $stage,
            'position' => $position,
            'closed_at' => $stage->isClosed() ? now() : null,
        ]);
    }
}
