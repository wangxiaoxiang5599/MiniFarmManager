<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\AnimalMovement;
use App\Models\Paddock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalMovement>
 */
class AnimalMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'from_paddock_id' => null,
            'to_paddock_id' => Paddock::factory(),
            'moved_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function between(?Paddock $from, ?Paddock $to): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_paddock_id' => $from?->id,
            'to_paddock_id' => $to?->id,
        ]);
    }
}
