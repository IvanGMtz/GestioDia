<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
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
            'role' => MemberRole::Employee,
            'name' => $this->faker->firstName(),
            'email' => null,
            'email_verified_at' => null,
            'active' => true,
            'last_seen_at' => null,
        ];
    }

    public function employer(): static
    {
        return $this->state(fn () => ['role' => MemberRole::Employer]);
    }
}
