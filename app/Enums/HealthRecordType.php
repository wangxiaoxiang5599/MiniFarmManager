<?php

namespace App\Enums;

enum HealthRecordType: string
{
    use HasOptions;

    case Vaccination = 'vaccination';
    case Treatment = 'treatment';
    case Injury = 'injury';
    case Checkup = 'checkup';
    case Other = 'other';
}
