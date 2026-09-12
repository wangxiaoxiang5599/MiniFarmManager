<?php

namespace Tests\Unit;

use App\Models\Animal;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AnimalAgeTest extends TestCase
{
    #[TestWith(['2026-09-12', '0d'])]
    #[TestWith(['2026-09-08', '4d'])]
    #[TestWith(['2026-02-08', '7m 4d'])]
    #[TestWith(['2026-02-12', '7m'])]
    #[TestWith(['2025-01-20', '1y 7m'])]
    #[TestWith(['2023-09-12', '3y'])]
    public function test_age_is_reported_in_whole_days_months_and_years(string $dateOfBirth, string $expected): void
    {
        Carbon::setTestNow('2026-09-12 23:30:00');

        $animal = new Animal(['date_of_birth' => $dateOfBirth]);

        $this->assertSame($expected, $animal->ageLabel());

        Carbon::setTestNow();
    }
}
