<?php

namespace Database\Factories;

use App\Enums\AnimalStatus;
use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag_number' => fake()->unique()->bothify('??-####'),
            'name' => fake()->optional(0.6)->firstName(),
            'species' => fake()->randomElement(Species::cases()),
            'sex' => fake()->randomElement(Sex::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-8 years', '-3 months'),
            'breed' => fake()->optional(0.7)->word(),
            'status' => AnimalStatus::Active,
            'notes' => null,
            'current_paddock_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnimalStatus::Active,
        ]);
    }

    /**
     * Sold animals never occupy a paddock.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnimalStatus::Sold,
            'current_paddock_id' => null,
        ]);
    }

    /**
     * Deceased animals never occupy a paddock.
     */
    public function deceased(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnimalStatus::Deceased,
            'current_paddock_id' => null,
        ]);
    }

    /**
     * Place the animal directly in a paddock, bypassing movement history.
     * Use MoveAnimal in application code; this is for test setup only.
     */
    public function inPaddock(Paddock $paddock): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_paddock_id' => $paddock->id,
        ]);
    }

    public function species(Species $species): static
    {
        return $this->state(fn (array $attributes): array => [
            'species' => $species,
        ]);
    }
}
