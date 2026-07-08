<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberDevice>
 */
class MemberDeviceFactory extends Factory
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
            'device_token' => $this->faker->uuid(),
            'last_used_at' => now(),
        ];
    }
}
