<?php

namespace App\Enums;

enum OccupancyState: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Full = 'full';

    public static function fromRatio(float $ratio): self
    {
        $threshold = (float) config('farm.capacity_warning_threshold', 0.8);

        return match (true) {
            $ratio >= 1.0 => self::Full,
            $ratio >= $threshold => self::Warning,
            default => self::Ok,
        };
    }
}
