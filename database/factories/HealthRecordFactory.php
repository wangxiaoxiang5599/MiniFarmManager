<?php

namespace Database\Factories;

use App\Enums\HealthRecordType;
use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = HealthRecordType::from(fake()->randomElement(HealthRecordType::values()));

        return [
            'animal_id' => Animal::factory(),
            'recorded_on' => fake()->dateTimeBetween('-1 year', 'now'),
            'type' => $type,
            'description' => match ($type) {
                HealthRecordType::Vaccination => fake()->randomElement(['5-in-1 vaccine', 'Clostridial booster', 'Leptospirosis vaccine']),
                HealthRecordType::Treatment => fake()->randomElement(['Drenched for worms', 'Antibiotic course', 'Foot bath']),
                HealthRecordType::Injury => fake()->randomElement(['Cut on hind leg', 'Lame front left', 'Horn injury']),
                HealthRecordType::Checkup => fake()->randomElement(['Routine condition score', 'Pregnancy test', 'Weight check']),
                HealthRecordType::Other => fake()->sentence(3),
            },
            'notes' => fake()->optional(0.4)->sentence(),
        ];
    }
}
