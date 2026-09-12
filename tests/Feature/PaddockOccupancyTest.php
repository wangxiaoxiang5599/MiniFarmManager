<?php

namespace Tests\Feature;

use App\Enums\OccupancyState;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PaddockOccupancyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([7, OccupancyState::Ok])]
    #[TestWith([8, OccupancyState::Warning])]
    #[TestWith([9, OccupancyState::Warning])]
    #[TestWith([10, OccupancyState::Full])]
    public function test_occupancy_state_follows_the_warning_threshold(int $animals, OccupancyState $expected): void
    {
        config(['farm.capacity_warning_threshold' => 0.8]);

        $paddock = Paddock::factory()->withCapacity(10)->create();
        Animal::factory()->count($animals)->inPaddock($paddock)->create();

        $this->assertSame($expected, $paddock->fresh()->occupancyState());
    }

    public function test_only_active_animals_count_towards_occupancy(): void
    {
        $paddock = Paddock::factory()->withCapacity(4)->create();
        Animal::factory()->count(2)->inPaddock($paddock)->create();
        Animal::factory()->sold()->create(['current_paddock_id' => $paddock->id]);
        Animal::factory()->deceased()->create(['current_paddock_id' => $paddock->id]);

        $paddock = Paddock::query()->withOccupancy()->find($paddock->id);

        $this->assertSame(2, $paddock->occupancy);
        $this->assertSame(2, $paddock->remainingCapacity());
        $this->assertTrue($paddock->hasRoom());
    }

    public function test_a_full_paddock_has_no_room(): void
    {
        $paddock = Paddock::factory()->withCapacity(2)->create();
        Animal::factory()->count(2)->inPaddock($paddock)->create();

        $this->assertFalse($paddock->fresh()->hasRoom());
        $this->assertSame(0, $paddock->fresh()->remainingCapacity());
    }
}
