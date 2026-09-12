<?php

namespace App\Http\Controllers;

use App\Actions\ChangeAnimalStatus;
use App\Actions\MoveAnimal;
use App\Enums\AnimalStatus;
use App\Enums\HealthRecordType;
use App\Enums\Sex;
use App\Enums\Species;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Http\Resources\AnimalMovementResource;
use App\Http\Resources\AnimalResource;
use App\Http\Resources\AnimalSummaryResource;
use App\Http\Resources\HealthRecordResource;
use App\Http\Resources\PaddockResource;
use App\Models\Animal;
use App\Models\Paddock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnimalController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'species' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'paddock' => ['nullable', 'integer'],
        ]);

        $animals = Animal::query()
            ->with('currentPaddock')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $q) => $q
                    ->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"),
            ))
            ->when($filters['species'] ?? null, fn (Builder $query, string $species) => $query->where('species', $species))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['paddock'] ?? null, fn (Builder $query, int $paddock) => $query->where('current_paddock_id', $paddock))
            ->orderBy('tag_number')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('animals/Index', [
            'animals' => AnimalSummaryResource::collection($animals),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'species' => $filters['species'] ?? '',
                'status' => $filters['status'] ?? '',
                'paddock' => $filters['paddock'] ?? '',
            ],
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('animals/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreAnimalRequest $request, MoveAnimal $moveAnimal): RedirectResponse
    {
        $validated = $request->validated();
        $paddock = isset($validated['paddock_id']) ? Paddock::findOrFail($validated['paddock_id']) : null;

        try {
            $animal = DB::transaction(function () use ($validated, $paddock, $moveAnimal): Animal {
                $animal = Animal::create(collect($validated)->except('paddock_id')->all());

                if ($paddock !== null) {
                    $moveAnimal->handle($animal, $paddock, notes: 'Initial placement.');
                }

                return $animal;
            });
        } catch (ValidationException $exception) {
            // MoveAnimal reports on to_paddock_id; this form calls the field paddock_id.
            throw ValidationException::withMessages([
                'paddock_id' => $exception->errors()['to_paddock_id'] ?? $exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Animal added.')]);

        return to_route('animals.show', $animal);
    }

    public function show(Animal $animal): Response
    {
        $animal->load('currentPaddock');

        $movements = $animal->movements()
            ->with(['fromPaddock', 'toPaddock'])
            ->latest('moved_at')
            ->latest('id')
            ->get();

        $healthRecords = $animal->healthRecords()
            ->latest('recorded_on')
            ->latest('id')
            ->get();

        $paddocks = Paddock::query()->withOccupancy()->orderBy('name')->get();

        return Inertia::render('animals/Show', [
            'animal' => AnimalResource::make($animal)->resolve(),
            'movements' => AnimalMovementResource::collection($movements)->resolve(),
            'healthRecords' => HealthRecordResource::collection($healthRecords)->resolve(),
            'paddocks' => PaddockResource::collection($paddocks)->resolve(),
            'healthRecordTypes' => HealthRecordType::options(),
        ]);
    }

    public function edit(Animal $animal): Response
    {
        $animal->load('currentPaddock');

        return Inertia::render('animals/Edit', [
            'animal' => AnimalResource::make($animal)->resolve(),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateAnimalRequest $request, Animal $animal, ChangeAnimalStatus $changeStatus): RedirectResponse
    {
        $validated = $request->validated();
        $status = AnimalStatus::from($validated['status']);

        DB::transaction(function () use ($animal, $validated, $status, $changeStatus): void {
            $animal->update(collect($validated)->except('status')->all());
            $changeStatus->handle($animal, $status);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Animal updated.')]);

        return to_route('animals.show', $animal);
    }

    /**
     * Select options shared by the animal forms and filters.
     *
     * @return array{species: array<int, array{value: string, label: string}>, sexes: array<int, array{value: string, label: string}>, statuses: array<int, array{value: string, label: string}>, paddocks: array<int, mixed>}
     */
    private function options(): array
    {
        $paddocks = Paddock::query()->withOccupancy()->orderBy('name')->get();

        return [
            'species' => Species::options(),
            'sexes' => Sex::options(),
            'statuses' => AnimalStatus::options(),
            'paddocks' => PaddockResource::collection($paddocks)->resolve(),
        ];
    }
}
