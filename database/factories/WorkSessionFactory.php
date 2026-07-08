<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Team;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSession>
 */
class WorkSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockedIn = now()->setTime(8, 0);

        return [
            'team_id' => Team::factory(),
            'member_id' => Member::factory(),
            'work_date' => $clockedIn->toDateString(),
            'clocked_in_at' => $clockedIn,
            'clocked_out_at' => $clockedIn->clone()->addHours(8),
            'auto_closed' => false,
            'edited_by_member_id' => null,
            'edit_reason' => null,
            'original_values' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['clocked_out_at' => null]);
    }
}
