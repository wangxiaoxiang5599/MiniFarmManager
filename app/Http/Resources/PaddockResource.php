<?php

namespace App\Http\Resources;

use App\Models\Paddock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Paddock
 */
class PaddockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'notes' => $this->notes,
            'occupancy' => $this->occupancy,
            'remaining_capacity' => $this->remainingCapacity(),
            'occupancy_ratio' => round($this->occupancyRatio(), 3),
            'occupancy_state' => $this->occupancyState()->value,
            'has_room' => $this->hasRoom(),
        ];
    }
}
