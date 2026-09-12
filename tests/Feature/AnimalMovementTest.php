<?php

namespace Tests\Feature;

use App\Actions\ChangeAnimalStatus;
use App\Actions\MoveAnimal;
use App\Enums\AnimalStatus;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnimalMovementTest extends TestCase
{
    use RefreshDatabase;

    private function moveAnimal(): MoveAnimal
    {
        return $this->app->make(MoveAnimal::class);
    }

    public function test_moving_an_animal_updates_its_paddock_and_appends_history(): void
    {
        $from = Paddock::factory()->create();
        $to = Paddock::factory()->withCapacity(5)->create();
        $animal = Animal::factory()->inPaddock($from)->create();

        $movement = $this->moveAnimal()->handle($animal, $to, notes: 'Rotating pasture');

        $this->assertSame($to->id, $animal->fresh()->current_paddock_id);
        $this->assertSame($from->id, $movement->from_paddock_id);
        $this->assertSame($to->id, $movement->to_paddock_id);
        $this->assertSame('Rotating pasture', $movement->notes);
        $this->assertSame(0, $from->fresh()->occupancy);
        $this->assertSame(1, $to->fresh()->occupancy);
    }

    public function test_moving_into_a_full_paddock_is_rejected(): void
    {
        $full = Paddock::factory()->withCapacity(2)->create(['name' => 'Full']);
        Animal::factory()->count(2)->inPaddock($full)->create();
        $animal = Animal::factory()->create();

        try {
            $this->moveAnimal()->handle($animal, $full);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Full is full (2 of 2). Move an animal out first.'],
                $exception->errors()['to_paddock_id'],
            );
        }

        $this->assertNull($animal->fresh()->current_paddock_id);
        $this->assertSame(0, $animal->movements()->count());
        $this->assertSame(2, $full->fresh()->occupancy);
    }

    public function test_a_paddock_with_one_place_left_accepts_exactly_one_more_animal(): void
    {
        $paddock = Paddock::factory()->withCapacity(3)->create();
        Animal::factory()->count(2)->inPaddock($paddock)->create();

        $this->moveAnimal()->handle(Animal::factory()->create(), $paddock);

        $this->assertSame(3, $paddock->fresh()->occupancy);
        $this->expectException(ValidationException::class);
        $this->moveAnimal()->handle(Animal::factory()->create(), $paddock);
    }

    public function test_moving_to_the_paddock_the_animal_is_already_in_is_rejected(): void
    {
        $paddock = Paddock::factory()->create(['name' => 'Same']);
        $animal = Animal::factory()->inPaddock($paddock)->create(['tag_number' => 'T-1']);

        try {
            $this->moveAnimal()->handle($animal, $paddock);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(['T-1 is already in Same.'], $exception->errors()['to_paddock_id']);
        }

        $this->assertSame(0, $animal->movements()->count());
    }

    public function test_only_active_animals_can_be_moved_into_a_paddock(): void
    {
        $paddock = Paddock::factory()->create();
        $animal = Animal::factory()->sold()->create(['tag_number' => 'S-1']);

        try {
            $this->moveAnimal()->handle($animal, $paddock);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Only active animals can be moved. S-1 is Sold.'],
                $exception->errors()['to_paddock_id'],
            );
        }
    }

    public function test_current_paddock_always_matches_the_latest_movement(): void
    {
        [$a, $b, $c] = Paddock::factory()->count(3)->withCapacity(5)->create();
        $animal = Animal::factory()->create();

        $this->moveAnimal()->handle($animal, $a, now()->subDays(3));
        $this->moveAnimal()->handle($animal, $b, now()->subDays(2));
        $this->moveAnimal()->handle($animal, $c, now()->subDay());
        $this->moveAnimal()->handle($animal, null, now());

        $latest = $animal->movements()->latest('moved_at')->latest('id')->first();

        $this->assertSame(4, $animal->movements()->count());
        $this->assertNull($animal->fresh()->current_paddock_id);
        $this->assertSame($animal->fresh()->current_paddock_id, $latest->to_paddock_id);
        $this->assertSame($c->id, $latest->from_paddock_id);
    }

    public function test_changing_status_away_from_active_frees_the_paddock_place(): void
    {
        $paddock = Paddock::factory()->withCapacity(1)->create();
        $animal = Animal::factory()->inPaddock($paddock)->create();

        $this->app->make(ChangeAnimalStatus::class)->handle($animal, AnimalStatus::Deceased);

        $this->assertSame(AnimalStatus::Deceased, $animal->fresh()->status);
        $this->assertNull($animal->fresh()->current_paddock_id);
        $this->assertTrue($paddock->fresh()->hasRoom());

        $movement = $animal->movements()->sole();
        $this->assertSame($paddock->id, $movement->from_paddock_id);
        $this->assertNull($movement->to_paddock_id);
        $this->assertSame('Left paddock: marked as Deceased.', $movement->notes);
    }

    public function test_changing_status_of_an_unplaced_animal_records_no_movement(): void
    {
        $animal = Animal::factory()->create();

        $this->app->make(ChangeAnimalStatus::class)->handle($animal, AnimalStatus::Sold);

        $this->assertSame(AnimalStatus::Sold, $animal->fresh()->status);
        $this->assertSame(0, $animal->movements()->count());
    }

    public function test_reactivating_an_animal_leaves_it_unplaced(): void
    {
        $animal = Animal::factory()->sold()->create();

        $this->app->make(ChangeAnimalStatus::class)->handle($animal, AnimalStatus::Active);

        $this->assertSame(AnimalStatus::Active, $animal->fresh()->status);
        $this->assertNull($animal->fresh()->current_paddock_id);
    }
}
