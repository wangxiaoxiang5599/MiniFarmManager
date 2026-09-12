<?php

namespace Tests\Feature;

use App\Enums\HealthRecordType;
use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_health_record_can_be_added_to_an_animal(): void
    {
        $animal = Animal::factory()->create();

        $this->post(route('animals.health-records.store', $animal), [
            'recorded_on' => '2026-08-20',
            'type' => 'vaccination',
            'description' => '5-in-1 vaccine',
            'notes' => 'Second dose',
        ])
            ->assertRedirect(route('animals.show', $animal))
            ->assertSessionHasNoErrors();

        $record = $animal->healthRecords()->sole();

        $this->assertSame('2026-08-20', $record->recorded_on->toDateString());
        $this->assertSame(HealthRecordType::Vaccination, $record->type);
        $this->assertSame('5-in-1 vaccine', $record->description);
        $this->assertSame('Second dose', $record->notes);
    }

    public function test_health_records_require_a_valid_date_type_and_description(): void
    {
        $animal = Animal::factory()->create();

        $this->post(route('animals.health-records.store', $animal), [
            'recorded_on' => now()->addDay()->toDateString(),
            'type' => 'haircut',
            'description' => '',
        ])->assertSessionHasErrors(['recorded_on', 'type', 'description']);

        $this->assertSame(0, HealthRecord::count());
    }

    public function test_the_animal_page_lists_health_records_newest_first(): void
    {
        $animal = Animal::factory()->create();
        HealthRecord::factory()->for($animal)->create(['recorded_on' => '2026-01-10', 'description' => 'Older']);
        HealthRecord::factory()->for($animal)->create(['recorded_on' => '2026-06-01', 'description' => 'Newer']);
        HealthRecord::factory()->create(['description' => 'Other animal']);

        $this->get(route('animals.show', $animal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('healthRecords', 2)
                ->where('healthRecords.0.description', 'Newer')
                ->where('healthRecords.1.description', 'Older')
                ->has('healthRecordTypes', count(HealthRecordType::cases()))
            );
    }
}
