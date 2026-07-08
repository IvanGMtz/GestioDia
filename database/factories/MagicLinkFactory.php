<?php

namespace Database\Factories;

use App\Models\MagicLink;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MagicLink>
 */
class MagicLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'token' => hash('sha256', $this->faker->uuid()),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
        ];
    }
}
