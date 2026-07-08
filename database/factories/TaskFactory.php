<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'recurring_task_id' => null,
            'task_date' => now()->toDateString(),
            'title' => $this->faker->sentence(3),
            'description' => null,
            'assigned_member_id' => null,
            'requires_photo' => false,
            'completed_at' => null,
            'completed_by_member_id' => null,
            'completion_note' => null,
            'photo_path' => null,
            'photo_pruned_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
