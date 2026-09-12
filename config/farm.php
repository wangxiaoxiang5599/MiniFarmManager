<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paddock Capacity Warning Threshold
    |--------------------------------------------------------------------------
    |
    | A paddock whose occupancy ratio (active animals / capacity) reaches this
    | value is flagged as "warning" across the UI. A ratio of 1.0 is "full".
    |
    */

    'capacity_warning_threshold' => (float) env('FARM_CAPACITY_WARNING_THRESHOLD', 0.8),

];
