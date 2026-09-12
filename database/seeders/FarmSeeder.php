<?php

namespace Database\Seeders;

use App\Actions\ChangeAnimalStatus;
use App\Actions\MoveAnimal;
use App\Enums\AnimalStatus;
use App\Enums\Species;
use App\Models\Animal;
use App\Models\HealthRecord;
use App\Models\Paddock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A small, believable demo farm. Movements go through MoveAnimal so the
 * history and current paddock stay consistent and the capacity rules hold.
 */
class FarmSeeder extends Seeder
{
    public function __construct(
        private MoveAnimal $moveAnimal,
        private ChangeAnimalStatus $changeStatus,
    ) {}

    public function run(): void
    {
        $paddocks = $this->createPaddocks();
        $animals = $this->createAnimals();

        $this->placeAnimals($animals, $paddocks);
        $this->shuffleSomeAnimals($animals, $paddocks);
        $this->retireSomeAnimals($animals);
        $this->recordHealthEvents($animals);
    }

    /**
     * @return array<string, Paddock>
     */
    private function createPaddocks(): array
    {
        $definitions = [
            'north' => ['North Paddock', 12, 'Good shade along the tree line.'],
            'river' => ['River Flat', 20, 'Floods after heavy rain — move stock to Top Hill.'],
            'home' => ['Home Paddock', 5, 'Closest to the yards; used for animals under treatment.'],
            'top' => ['Top Hill', 15, null],
            'orchard' => ['Orchard Paddock', 6, 'Chickens only.'],
        ];

        $paddocks = [];

        foreach ($definitions as $key => [$name, $capacity, $notes]) {
            $paddocks[$key] = Paddock::factory()->create([
                'name' => $name,
                'capacity' => $capacity,
                'notes' => $notes,
            ]);
        }

        return $paddocks;
    }

    /**
     * @return array<string, Collection<int, Animal>>
     */
    private function createAnimals(): array
    {
        return [
            'cattle' => Animal::factory()->count(14)->species(Species::Cattle)->sequence(
                fn ($sequence) => ['tag_number' => sprintf('CT-%03d', $sequence->index + 1), 'breed' => 'Angus'],
            )->create(),
            'sheep' => Animal::factory()->count(12)->species(Species::Sheep)->sequence(
                fn ($sequence) => ['tag_number' => sprintf('SH-%03d', $sequence->index + 1), 'breed' => 'Romney'],
            )->create(),
            'goats' => Animal::factory()->count(4)->species(Species::Goat)->sequence(
                fn ($sequence) => ['tag_number' => sprintf('GT-%03d', $sequence->index + 1), 'breed' => 'Boer'],
            )->create(),
            'chickens' => Animal::factory()->count(6)->species(Species::Chicken)->sequence(
                fn ($sequence) => ['tag_number' => sprintf('CH-%03d', $sequence->index + 1), 'breed' => null],
            )->create(),
        ];
    }

    /**
     * Initial placements, spread over the last few months.
     *
     * @param  array<string, Collection<int, Animal>>  $animals
     * @param  array<string, Paddock>  $paddocks
     */
    private function placeAnimals(array $animals, array $paddocks): void
    {
        $started = Carbon::now()->subMonths(4)->startOfDay();

        // North Paddock: 10 of 12 cattle -> nearly full (warning).
        foreach ($animals['cattle']->take(10) as $offset => $animal) {
            $this->moveAnimal->handle($animal, $paddocks['north'], $started->copy()->addDays($offset), 'Initial placement.');
        }

        // Remaining cattle start on River Flat with the sheep.
        foreach ($animals['cattle']->skip(10) as $offset => $animal) {
            $this->moveAnimal->handle($animal, $paddocks['river'], $started->copy()->addDays($offset), 'Initial placement.');
        }

        foreach ($animals['sheep'] as $offset => $animal) {
            $this->moveAnimal->handle($animal, $paddocks['river'], $started->copy()->addDays(3 + $offset), 'Initial placement.');
        }

        // Home Paddock: all 4 goats plus one sheep -> full.
        foreach ($animals['goats'] as $offset => $animal) {
            $this->moveAnimal->handle($animal, $paddocks['home'], $started->copy()->addWeeks(1)->addDays($offset), 'Initial placement.');
        }

        // Orchard: chickens, leaving one unplaced so the dashboard shows it.
        foreach ($animals['chickens']->take(5) as $offset => $animal) {
            $this->moveAnimal->handle($animal, $paddocks['orchard'], $started->copy()->addWeeks(2)->addDays($offset), 'Initial placement.');
        }
    }

    /**
     * A handful of later moves so the history and "recent movements" list
     * have something to show.
     *
     * @param  array<string, Collection<int, Animal>>  $animals
     * @param  array<string, Paddock>  $paddocks
     */
    private function shuffleSomeAnimals(array $animals, array $paddocks): void
    {
        $lastMonth = Carbon::now()->subMonth();

        foreach ($animals['sheep']->take(6) as $offset => $sheep) {
            $this->moveAnimal->handle($sheep, $paddocks['top'], $lastMonth->copy()->addDays($offset), 'Rotating onto fresh pasture.');
        }

        $lameSheep = $animals['sheep']->get(6);
        $this->moveAnimal->handle($lameSheep, $paddocks['home'], Carbon::now()->subDays(9)->setTime(8, 15), 'Lame — keeping close to the yards for treatment.');

        $this->moveAnimal->handle($animals['cattle']->get(10), $paddocks['top'], Carbon::now()->subDays(5)->setTime(14, 0), null);
        $this->moveAnimal->handle($animals['cattle']->get(11), $paddocks['top'], Carbon::now()->subDays(5)->setTime(14, 5), null);
    }

    /**
     * @param  array<string, Collection<int, Animal>>  $animals
     */
    private function retireSomeAnimals(array $animals): void
    {
        $this->changeStatus->handle($animals['cattle']->get(12), AnimalStatus::Sold);
        $this->changeStatus->handle($animals['cattle']->get(13), AnimalStatus::Sold);
        $this->changeStatus->handle($animals['sheep']->get(11), AnimalStatus::Deceased);
    }

    /**
     * @param  array<string, Collection<int, Animal>>  $animals
     */
    private function recordHealthEvents(array $animals): void
    {
        foreach ($animals['cattle']->take(10) as $animal) {
            HealthRecord::factory()->for($animal)->create([
                'recorded_on' => Carbon::now()->subMonths(3)->toDateString(),
                'type' => 'vaccination',
                'description' => '7-in-1 clostridial vaccine',
                'notes' => null,
            ]);
        }

        HealthRecord::factory()->for($animals['sheep']->get(6))->create([
            'recorded_on' => Carbon::now()->subDays(9)->toDateString(),
            'type' => 'injury',
            'description' => 'Lame on front left',
            'notes' => 'Foot trimmed and sprayed. Re-check in a week.',
        ]);

        HealthRecord::factory()->for($animals['sheep']->get(6))->create([
            'recorded_on' => Carbon::now()->subDays(2)->toDateString(),
            'type' => 'checkup',
            'description' => 'Follow-up on lameness',
            'notes' => 'Improving, still slightly favouring the leg.',
        ]);

        foreach ($animals['goats'] as $goat) {
            HealthRecord::factory()->for($goat)->create([
                'recorded_on' => Carbon::now()->subWeeks(3)->toDateString(),
                'type' => 'treatment',
                'description' => 'Drenched for worms',
            ]);
        }

        HealthRecord::factory()->count(6)->create([
            'animal_id' => fn () => $animals['sheep']->random()->id,
        ]);
    }
}
