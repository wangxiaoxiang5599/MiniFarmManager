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
            'name' => fake()->unique()->randomElement([
                'North Paddock', 'South Paddock', 'East Paddock', 'West Paddock',
                'River Flat', 'Top Hill', 'Home Paddock', 'Back Block',
                'Creek Paddock', 'Long Paddock', 'Orchard Paddock', 'Ridge Paddock',
            ]),
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
