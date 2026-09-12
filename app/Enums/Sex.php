<?php

namespace App\Enums;

enum Sex: string
{
    use HasOptions;

    case Male = 'male';
    case Female = 'female';
}
