<?php

namespace App\Http\Controllers;

use App\Actions\MoveAnimal;
use App\Http\Requests\StoreAnimalMovementRequest;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AnimalMovementController extends Controller
{
    public function store(StoreAnimalMovementRequest $request, Animal $animal, MoveAnimal $moveAnimal): RedirectResponse
    {
        $validated = $request->validated();
        $paddock = Paddock::query()->findOrFail((int) $validated['to_paddock_id']);

        $moveAnimal->handle(
            $animal,
            $paddock,
            isset($validated['moved_at']) ? Carbon::parse($validated['moved_at']) : null,
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':animal moved to :paddock.', [
                'animal' => $animal->tag_number,
                'paddock' => $paddock->name,
            ]),
        ]);

        return to_route('animals.show', $animal);
    }
}
