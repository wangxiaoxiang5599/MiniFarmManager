<?php

namespace App\Actions;

use App\Models\Animal;
use App\Models\AnimalMovement;
use App\Models\Paddock;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Relocates an animal and records the movement.
 *
 * This is the only code path that writes `animals.current_paddock_id`, which
 * keeps that denormalised column in step with the movement history.
 */
class MoveAnimal
{
    /**
     * @param  Paddock|null  $to  Null means the animal leaves its paddock (e.g. sold).
     *
     * @throws ValidationException
     */
    public function handle(
        Animal $animal,
        ?Paddock $to,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): AnimalMovement {
        return DB::transaction(function () use ($animal, $to, $movedAt, $notes): AnimalMovement {
            // Work from the locked row, but keep the caller's instance in sync
            // so it never carries a stale current_paddock_id afterwards.
            $locked = Animal::query()->lockForUpdate()->findOrFail($animal->id);
            $animal->setRawAttributes($locked->getAttributes(), true);

            if ($to !== null) {
                $this->ensureAnimalCanBePlaced($animal, $to);
            }

            $movement = $animal->movements()->create([
                'from_paddock_id' => $animal->current_paddock_id,
                'to_paddock_id' => $to?->id,
                'moved_at' => $movedAt ?? now(),
                'notes' => $notes,
            ]);

            $animal->forceFill(['current_paddock_id' => $to?->id])->save();

            return $movement;
        });
    }

    /**
     * Rules for entering a paddock. The target row is locked for the rest of
     * the transaction so that two concurrent moves cannot both pass the
     * capacity check.
     *
     * @throws ValidationException
     */
    private function ensureAnimalCanBePlaced(Animal $animal, Paddock $to): void
    {
        if (! $animal->isActive()) {
            throw ValidationException::withMessages([
                'to_paddock_id' => "Only active animals can be moved. {$animal->tag_number} is {$animal->status->label()}.",
            ]);
        }

        if ($animal->current_paddock_id === $to->id) {
            throw ValidationException::withMessages([
                'to_paddock_id' => "{$animal->tag_number} is already in {$to->name}.",
            ]);
        }

        $to = Paddock::query()->lockForUpdate()->withOccupancy()->findOrFail($to->id);

        if (! $to->hasRoom()) {
            throw ValidationException::withMessages([
                'to_paddock_id' => "{$to->name} is full ({$to->occupancy} of {$to->capacity}). Move an animal out first.",
            ]);
        }
    }
}
