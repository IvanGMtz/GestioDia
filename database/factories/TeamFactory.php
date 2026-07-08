<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('??????')).'-'.$this->faker->numerify('####'),
            'name' => $this->faker->company(),
            'max_members' => 5,
            'tasks_generated_until' => null,
            'settings' => ['timezone' => 'Europe/Madrid'],
        ];
    }
}
