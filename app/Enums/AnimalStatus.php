<?php

namespace App\Enums;

enum AnimalStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Sold = 'sold';
    case Deceased = 'deceased';

    /**
     * Only active animals occupy paddock capacity.
     */
    public function occupiesPaddock(): bool
    {
        return $this === self::Active;
    }
}
