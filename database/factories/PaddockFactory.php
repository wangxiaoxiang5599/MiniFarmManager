<?php

namespace Database\Factories;

use App\Models\Paddock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paddock>
 */
class PaddockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'North', 'South', 'East', 'West', 'River', 'Hill', 'Home', 'Creek', 'Ridge', 'Orchard',
            ]).' Paddock '.fake()->unique()->numberBetween(1, 9999),
            'capacity' => fake()->numberBetween(5, 40),
            'notes' => null,
        ];
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes): array => [
            'capacity' => $capacity,
        ]);
    }
}
