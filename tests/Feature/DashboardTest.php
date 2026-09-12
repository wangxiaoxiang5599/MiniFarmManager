<?php

namespace Tests\Feature;

use App\Enums\Species;
use App\Models\Animal;
use App\Models\AnimalMovement;
use App\Models\Paddock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_visit_the_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_home_redirects_to_the_dashboard(): void
    {
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_it_reports_animal_counts_and_species_breakdown_for_active_animals_only(): void
    {
        $paddock = Paddock::factory()->withCapacity(10)->create();
        Animal::factory()->count(3)->species(Species::Cattle)->inPaddock($paddock)->create();
        Animal::factory()->count(2)->species(Species::Sheep)->inPaddock($paddock)->create();
        Animal::factory()->species(Species::Goat)->create();
        Animal::factory()->sold()->species(Species::Cattle)->create();

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.active_animals', 6)
                ->where('stats.unplaced_animals', 1)
                ->where('stats.paddocks', 1)
                ->where('stats.total_occupancy', 5)
                ->where('stats.total_capacity', 10)
                ->has('animalsBySpecies', 3)
                ->where('animalsBySpecies.0', ['species' => 'cattle', 'label' => 'Cattle', 'count' => 3])
                ->where('animalsBySpecies.1', ['species' => 'sheep', 'label' => 'Sheep', 'count' => 2])
                ->where('animalsByStatus.0', ['status' => 'active', 'label' => 'Active', 'count' => 6])
                ->where('animalsByStatus.1', ['status' => 'sold', 'label' => 'Sold', 'count' => 1])
            );
    }

    public function test_it_lists_paddocks_at_or_above_the_warning_threshold(): void
    {
        $fine = Paddock::factory()->withCapacity(10)->create(['name' => 'Fine']);
        Animal::factory()->count(7)->inPaddock($fine)->create();

        $warning = Paddock::factory()->withCapacity(10)->create(['name' => 'Warning']);
        Animal::factory()->count(8)->inPaddock($warning)->create();

        $full = Paddock::factory()->withCapacity(2)->create(['name' => 'Full']);
        Animal::factory()->count(2)->inPaddock($full)->create();

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.paddocks_needing_attention', 2)
                ->has('attention', 2)
                ->where('attention.0.name', 'Full')
                ->where('attention.0.occupancy_state', 'full')
                ->where('attention.1.name', 'Warning')
                ->where('attention.1.occupancy_state', 'warning')
                ->has('paddocks', 3)
            );
    }

    public function test_it_shows_the_most_recent_movements_newest_first(): void
    {
        $animal = Animal::factory()->create(['tag_number' => 'MV-1']);
        $paddock = Paddock::factory()->create(['name' => 'Dest']);

        AnimalMovement::factory()->for($animal)->between(null, $paddock)->create(['moved_at' => now()->subDays(2)]);
        AnimalMovement::factory()->for($animal)->between($paddock, null)->create(['moved_at' => now()->subDay()]);
        AnimalMovement::factory()->count(12)->create(['moved_at' => now()->subWeeks(2)]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('recentMovements', 10)
                ->where('recentMovements.0.animal.tag_number', 'MV-1')
                ->where('recentMovements.0.from_paddock.name', 'Dest')
                ->where('recentMovements.0.to_paddock', null)
                ->where('recentMovements.1.to_paddock.name', 'Dest')
            );
    }
}
