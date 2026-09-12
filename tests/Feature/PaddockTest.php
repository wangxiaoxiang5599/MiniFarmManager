<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaddockTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_paddocks_with_occupancy(): void
    {
        $paddock = Paddock::factory()->withCapacity(10)->create(['name' => 'North']);
        Animal::factory()->count(3)->inPaddock($paddock)->create();
        Animal::factory()->sold()->create();

        $this->get(route('paddocks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('paddocks/Index')
                ->has('paddocks', 1)
                ->where('paddocks.0.name', 'North')
                ->where('paddocks.0.occupancy', 3)
                ->where('paddocks.0.remaining_capacity', 7)
                ->where('paddocks.0.occupancy_state', 'ok')
            );
    }

    public function test_a_paddock_can_be_created(): void
    {
        $response = $this->post(route('paddocks.store'), [
            'name' => 'River Flat',
            'capacity' => 25,
            'notes' => 'Good water access',
        ]);

        $paddock = Paddock::firstWhere('name', 'River Flat');

        $this->assertNotNull($paddock);
        $response->assertRedirect(route('paddocks.show', $paddock));
        $this->assertSame(25, $paddock->capacity);
        $this->assertSame('Good water access', $paddock->notes);
    }

    public function test_creating_a_paddock_requires_a_unique_name_and_positive_capacity(): void
    {
        Paddock::factory()->create(['name' => 'North']);

        $this->from(route('paddocks.create'))
            ->post(route('paddocks.store'), ['name' => 'North', 'capacity' => 0])
            ->assertRedirect(route('paddocks.create'))
            ->assertSessionHasErrors(['name', 'capacity']);

        $this->assertSame(1, Paddock::count());
    }

    public function test_a_paddock_can_be_updated(): void
    {
        $paddock = Paddock::factory()->withCapacity(10)->create(['name' => 'Old name']);

        $this->put(route('paddocks.update', $paddock), [
            'name' => 'New name',
            'capacity' => 12,
            'notes' => null,
        ])->assertRedirect(route('paddocks.show', $paddock));

        $paddock->refresh();

        $this->assertSame('New name', $paddock->name);
        $this->assertSame(12, $paddock->capacity);
    }

    public function test_capacity_cannot_be_reduced_below_current_occupancy(): void
    {
        $paddock = Paddock::factory()->withCapacity(10)->create();
        Animal::factory()->count(4)->inPaddock($paddock)->create();

        $this->from(route('paddocks.edit', $paddock))
            ->put(route('paddocks.update', $paddock), [
                'name' => $paddock->name,
                'capacity' => 3,
            ])
            ->assertRedirect(route('paddocks.edit', $paddock))
            ->assertSessionHasErrors([
                'capacity' => 'Capacity cannot be lower than the 4 animals currently in this paddock.',
            ]);

        $this->assertSame(10, $paddock->refresh()->capacity);
    }

    public function test_capacity_can_be_reduced_to_exactly_the_current_occupancy(): void
    {
        $paddock = Paddock::factory()->withCapacity(10)->create();
        Animal::factory()->count(4)->inPaddock($paddock)->create();

        $this->put(route('paddocks.update', $paddock), [
            'name' => $paddock->name,
            'capacity' => 4,
        ])->assertSessionHasNoErrors();

        $this->assertSame(4, $paddock->refresh()->capacity);
    }

    public function test_show_lists_only_active_animals_currently_in_the_paddock(): void
    {
        $paddock = Paddock::factory()->create();
        $other = Paddock::factory()->create();

        $inside = Animal::factory()->inPaddock($paddock)->create(['tag_number' => 'IN-001']);
        Animal::factory()->inPaddock($other)->create(['tag_number' => 'OUT-001']);
        Animal::factory()->sold()->create(['tag_number' => 'SOLD-001']);

        $this->get(route('paddocks.show', $paddock))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('paddocks/Show')
                ->where('paddock.id', $paddock->id)
                ->has('animals', 1)
                ->where('animals.0.id', $inside->id)
                ->where('animals.0.tag_number', 'IN-001')
            );
    }
}
