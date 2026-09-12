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

    public function test_a_movement_cannot_be_dated_before_the_latest_movement(): void
    {
        [$a, $b] = Paddock::factory()->count(2)->withCapacity(5)->create();
        $animal = Animal::factory()->create();

        $this->moveAnimal()->handle($animal, $a, now()->setTime(10, 0));

        try {
            $this->moveAnimal()->handle($animal, $b, now()->subDay());
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('moved_at', $exception->errors());
        }

        $this->assertSame($a->id, $animal->fresh()->current_paddock_id);
        $this->assertSame(1, $animal->movements()->count());
    }

    public function test_a_movement_may_share_the_timestamp_of_the_latest_movement(): void
    {
        [$a, $b] = Paddock::factory()->count(2)->withCapacity(5)->create();
        $animal = Animal::factory()->create();
        $at = now()->setTime(10, 0);

        $this->moveAnimal()->handle($animal, $a, $at);
        $this->moveAnimal()->handle($animal, $b, $at);

        $this->assertSame($b->id, $animal->fresh()->current_paddock_id);
    }

    public function test_the_endpoint_rejects_a_back_dated_movement_on_the_date_field(): void
    {
        [$a, $b] = Paddock::factory()->count(2)->withCapacity(5)->create();
        $animal = Animal::factory()->create();
        $this->moveAnimal()->handle($animal, $a, now());

        $this->from(route('animals.show', $animal))
            ->post(route('animals.movements.store', $animal), [
                'to_paddock_id' => $b->id,
                'moved_at' => now()->subDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('animals.show', $animal))
            ->assertSessionHasErrors(['moved_at']);

        $this->assertSame($a->id, $animal->fresh()->current_paddock_id);
    }

    public function test_an_animal_can_be_moved_through_the_endpoint(): void
    {
        $from = Paddock::factory()->create();
        $to = Paddock::factory()->withCapacity(5)->create(['name' => 'Target']);
        $animal = Animal::factory()->inPaddock($from)->create();

        $this->post(route('animals.movements.store', $animal), [
            'to_paddock_id' => $to->id,
            'moved_at' => '2026-09-01T09:30',
            'notes' => 'Weaning',
        ])
            ->assertRedirect(route('animals.show', $animal))
            ->assertSessionHasNoErrors();

        $movement = $animal->movements()->sole();

        $this->assertSame($to->id, $animal->fresh()->current_paddock_id);
        $this->assertSame($from->id, $movement->from_paddock_id);
        $this->assertSame('2026-09-01 09:30:00', $movement->moved_at->toDateTimeString());
        $this->assertSame('Weaning', $movement->notes);
    }

    public function test_the_endpoint_reports_a_full_paddock_on_the_paddock_field(): void
    {
        $full = Paddock::factory()->withCapacity(1)->create(['name' => 'Full']);
        Animal::factory()->inPaddock($full)->create();
        $animal = Animal::factory()->create();

        $this->from(route('animals.show', $animal))
            ->post(route('animals.movements.store', $animal), ['to_paddock_id' => $full->id])
            ->assertRedirect(route('animals.show', $animal))
            ->assertSessionHasErrors(['to_paddock_id' => 'Full is full (1 of 1). Move an animal out first.']);

        $this->assertNull($animal->fresh()->current_paddock_id);
    }

    public function test_the_endpoint_validates_its_input(): void
    {
        $animal = Animal::factory()->create();

        $this->post(route('animals.movements.store', $animal), [
            'to_paddock_id' => 999,
            'moved_at' => now()->addDay()->toIso8601String(),
        ])->assertSessionHasErrors(['to_paddock_id', 'moved_at']);

        $this->assertSame(0, $animal->movements()->count());
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

    public function test_the_caller_instance_is_kept_in_sync_after_a_move(): void
    {
        $paddock = Paddock::factory()->create();
        $animal = Animal::factory()->create();

        $this->moveAnimal()->handle($animal, $paddock);

        $this->assertSame($paddock->id, $animal->current_paddock_id);
        $this->assertFalse($animal->isDirty());
    }

    public function test_changing_status_on_a_stale_instance_still_frees_the_paddock(): void
    {
        $paddock = Paddock::factory()->withCapacity(1)->create();
        $animal = Animal::factory()->create();
        $stale = Animal::find($animal->id);

        $this->moveAnimal()->handle($animal, $paddock);
        $this->app->make(ChangeAnimalStatus::class)->handle($stale, AnimalStatus::Sold);

        $this->assertNull($animal->fresh()->current_paddock_id);
        $this->assertTrue($paddock->fresh()->hasRoom());
        $this->assertSame(2, $animal->movements()->count());
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
