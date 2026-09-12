<?php

namespace App\Enums;

enum Species: string
{
    use HasOptions;

    case Cattle = 'cattle';
    case Sheep = 'sheep';
    case Goat = 'goat';
    case Pig = 'pig';
    case Horse = 'horse';
    case Chicken = 'chicken';
    case Other = 'other';
}
