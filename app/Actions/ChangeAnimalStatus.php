<?php

namespace App\Actions;

use App\Enums\AnimalStatus;
use App\Models\Animal;
use Illuminate\Support\Facades\DB;

/**
 * Changes an animal's status. Leaving the active state also takes the animal
 * out of its paddock so it stops occupying capacity, with the departure
 * recorded in the movement history.
 */
class ChangeAnimalStatus
{
    public function __construct(private MoveAnimal $moveAnimal) {}

    public function handle(Animal $animal, AnimalStatus $status): Animal
    {
        return DB::transaction(function () use ($animal, $status): Animal {
            if ($animal->status === $status) {
                return $animal;
            }

            if (! $status->occupiesPaddock() && $animal->current_paddock_id !== null) {
                $this->moveAnimal->handle(
                    $animal,
                    to: null,
                    notes: "Left paddock: marked as {$status->label()}.",
                );
            }

            $animal->forceFill(['status' => $status])->save();

            return $animal->refresh();
        });
    }
}
