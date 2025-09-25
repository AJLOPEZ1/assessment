<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', '+6 months');
        $endDate = fake()->dateTimeBetween($startDate, '+1 year');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => User::factory(),
        ];
    }
}
