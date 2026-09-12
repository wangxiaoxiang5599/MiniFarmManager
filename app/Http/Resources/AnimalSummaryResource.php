<?php

namespace App\Http\Resources;

use App\Models\Animal;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact representation used in lists (animal index, paddock page, dashboard).
 *
 * @mixin Animal
 */
class AnimalSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag_number' => $this->tag_number,
            'name' => $this->name,
            'display_name' => $this->displayName(),
            'species' => $this->species->value,
            'species_label' => $this->species->label(),
            'sex' => $this->sex->value,
            'sex_label' => $this->sex->label(),
            'breed' => $this->breed,
            'date_of_birth' => $this->date_of_birth->toDateString(),
            'age' => $this->date_of_birth->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, short: true, parts: 2),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'current_paddock' => $this->whenLoaded('currentPaddock', fn () => $this->currentPaddock ? [
                'id' => $this->currentPaddock->id,
                'name' => $this->currentPaddock->name,
            ] : null),
        ];
    }
}
