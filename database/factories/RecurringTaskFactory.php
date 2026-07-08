<?php

namespace Database\Factories;

use App\Models\RecurringTask;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTask>
 */
class RecurringTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => $this->faker->sentence(3),
            'description' => null,
            'assigned_member_id' => null,
            'requires_photo' => false,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
