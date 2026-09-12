<?php

namespace App\Http\Resources;

use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HealthRecord
 */
class HealthRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recorded_on' => $this->recorded_on->toDateString(),
            'recorded_on_label' => $this->recorded_on->format('j M Y'),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            'notes' => $this->notes,
        ];
    }
}
