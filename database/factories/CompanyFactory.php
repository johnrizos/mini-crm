<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->company(),
            'domain' => fake()->domainName(),
            'industry' => fake()->randomElement(['Retail', 'SaaS', 'Travel', 'Finance', 'Healthcare', 'Media', 'Education']),
        ];
    }
}
