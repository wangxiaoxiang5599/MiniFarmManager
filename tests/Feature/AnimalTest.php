<?php

namespace Tests\Feature;

use App\Enums\AnimalStatus;
use App\Enums\Species;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnimalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'tag_number' => 'NZ-0001',
            'name' => 'Daisy',
            'species' => 'cattle',
            'sex' => 'female',
            'date_of_birth' => '2022-03-15',
            'breed' => 'Angus',
            'notes' => null,
            'paddock_id' => null,
            ...$overrides,
        ];
    }

    public function test_index_lists_animals_with_their_current_paddock(): void
    {
        $paddock = Paddock::factory()->create(['name' => 'North']);
        Animal::factory()->inPaddock($paddock)->create(['tag_number' => 'A-1']);
        Animal::factory()->create(['tag_number' => 'B-2']);

        $this->get(route('animals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('animals/Index')
                ->has('animals.data', 2)
                ->where('animals.data.0.tag_number', 'A-1')
                ->where('animals.data.0.current_paddock.name', 'North')
                ->where('animals.data.1.current_paddock', null)
            );
    }

    public function test_index_can_be_filtered_by_search_species_status_and_paddock(): void
    {
        $paddock = Paddock::factory()->create();
        Animal::factory()->inPaddock($paddock)->species(Species::Sheep)->create(['tag_number' => 'SH-1', 'name' => 'Woolly']);
        Animal::factory()->species(Species::Cattle)->create(['tag_number' => 'CT-1']);
        Animal::factory()->sold()->create(['tag_number' => 'SOLD-1']);

        $this->get(route('animals.index', ['search' => 'wool']))
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 1)->where('animals.data.0.tag_number', 'SH-1'));

        $this->get(route('animals.index', ['species' => 'cattle']))
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 1)->where('animals.data.0.tag_number', 'CT-1'));

        $this->get(route('animals.index', ['status' => 'sold']))
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 1)->where('animals.data.0.tag_number', 'SOLD-1'));

        $this->get(route('animals.index', ['paddock' => $paddock->id]))
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 1)->where('animals.data.0.tag_number', 'SH-1'));
    }

    public function test_an_animal_can_be_created_without_a_paddock(): void
    {
        $response = $this->post(route('animals.store'), $this->validPayload());

        $animal = Animal::firstWhere('tag_number', 'NZ-0001');

        $this->assertNotNull($animal);
        $response->assertRedirect(route('animals.show', $animal));
        $this->assertSame(AnimalStatus::Active, $animal->status);
        $this->assertNull($animal->current_paddock_id);
        $this->assertSame(0, $animal->movements()->count());
    }

    public function test_creating_an_animal_with_a_paddock_records_the_initial_placement(): void
    {
        $paddock = Paddock::factory()->withCapacity(5)->create();

        $this->post(route('animals.store'), $this->validPayload(['paddock_id' => $paddock->id]))
            ->assertSessionHasNoErrors();

        $animal = Animal::firstWhere('tag_number', 'NZ-0001');

        $this->assertSame($paddock->id, $animal->current_paddock_id);

        $movement = $animal->movements()->sole();
        $this->assertNull($movement->from_paddock_id);
        $this->assertSame($paddock->id, $movement->to_paddock_id);
    }

    public function test_creating_an_animal_into_a_full_paddock_is_rejected(): void
    {
        $paddock = Paddock::factory()->withCapacity(1)->create(['name' => 'Tiny']);
        Animal::factory()->inPaddock($paddock)->create();

        $this->from(route('animals.create'))
            ->post(route('animals.store'), $this->validPayload(['paddock_id' => $paddock->id]))
            ->assertRedirect(route('animals.create'))
            ->assertSessionHasErrors(['paddock_id' => 'Tiny is full (1 of 1). Move an animal out first.']);

        $this->assertNull(Animal::firstWhere('tag_number', 'NZ-0001'));
    }

    public function test_tag_number_must_be_unique_and_date_of_birth_cannot_be_in_the_future(): void
    {
        Animal::factory()->create(['tag_number' => 'NZ-0001']);

        $this->from(route('animals.create'))
            ->post(route('animals.store'), $this->validPayload([
                'date_of_birth' => now()->addDay()->toDateString(),
            ]))
            ->assertRedirect(route('animals.create'))
            ->assertSessionHasErrors(['tag_number', 'date_of_birth']);

        $this->assertSame(1, Animal::count());
    }

    public function test_species_and_sex_must_be_known_values(): void
    {
        $this->post(route('animals.store'), $this->validPayload(['species' => 'dragon', 'sex' => 'unknown']))
            ->assertSessionHasErrors(['species', 'sex']);
    }

    public function test_show_displays_the_animal_and_its_movement_history(): void
    {
        $from = Paddock::factory()->create(['name' => 'From']);
        $to = Paddock::factory()->create(['name' => 'To']);
        $animal = Animal::factory()->inPaddock($to)->create();
        $animal->movements()->create(['from_paddock_id' => null, 'to_paddock_id' => $from->id, 'moved_at' => now()->subDays(2)]);
        $animal->movements()->create(['from_paddock_id' => $from->id, 'to_paddock_id' => $to->id, 'moved_at' => now()->subDay()]);

        $this->get(route('animals.show', $animal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('animals/Show')
                ->where('animal.id', $animal->id)
                ->where('animal.current_paddock.name', 'To')
                ->has('movements', 2)
                ->where('movements.0.from_paddock.name', 'From')
                ->where('movements.0.to_paddock.name', 'To')
                ->where('movements.1.from_paddock', null)
            );
    }

    public function test_an_animal_can_be_updated(): void
    {
        $animal = Animal::factory()->create(['tag_number' => 'OLD-1', 'name' => null]);

        $this->put(route('animals.update', $animal), [
            'tag_number' => 'NEW-1',
            'name' => 'Bella',
            'species' => 'goat',
            'sex' => 'female',
            'date_of_birth' => '2021-01-01',
            'breed' => null,
            'status' => 'active',
            'notes' => 'Friendly',
        ])->assertRedirect(route('animals.show', $animal));

        $animal->refresh();

        $this->assertSame('NEW-1', $animal->tag_number);
        $this->assertSame('Bella', $animal->name);
        $this->assertSame('Friendly', $animal->notes);
    }

    public function test_marking_an_animal_as_sold_removes_it_from_its_paddock(): void
    {
        $paddock = Paddock::factory()->withCapacity(2)->create();
        $animal = Animal::factory()->inPaddock($paddock)->create();

        $this->put(route('animals.update', $animal), [
            'tag_number' => $animal->tag_number,
            'name' => $animal->name,
            'species' => $animal->species->value,
            'sex' => $animal->sex->value,
            'date_of_birth' => $animal->date_of_birth->toDateString(),
            'breed' => $animal->breed,
            'status' => 'sold',
            'notes' => null,
        ])->assertSessionHasNoErrors();

        $animal->refresh();

        $this->assertSame(AnimalStatus::Sold, $animal->status);
        $this->assertNull($animal->current_paddock_id);
        $this->assertSame(0, $paddock->fresh()->occupancy);

        $movement = $animal->movements()->sole();
        $this->assertSame($paddock->id, $movement->from_paddock_id);
        $this->assertNull($movement->to_paddock_id);
    }
}
