<?php

namespace App\Http\Resources;

use App\Models\AnimalMovement;
use App\Models\Paddock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AnimalMovement
 */
class AnimalMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'moved_at' => $this->moved_at->toIso8601String(),
            'moved_at_label' => $this->moved_at->format('j M Y, H:i'),
            'from_paddock' => $this->paddockSummary($this->fromPaddock),
            'to_paddock' => $this->paddockSummary($this->toPaddock),
            'notes' => $this->notes,
            'animal' => $this->whenLoaded('animal', fn () => [
                'id' => $this->animal->id,
                'tag_number' => $this->animal->tag_number,
                'display_name' => $this->animal->displayName(),
            ]),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function paddockSummary(?Paddock $paddock): ?array
    {
        return $paddock ? ['id' => $paddock->id, 'name' => $paddock->name] : null;
    }
}
