<?php

namespace App\Http\Resources;

use App\Models\Animal;
use Illuminate\Http\Request;

/**
 * Full representation for the animal detail and edit pages.
 *
 * @mixin Animal
 */
class AnimalResource extends AnimalSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'notes' => $this->notes,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
