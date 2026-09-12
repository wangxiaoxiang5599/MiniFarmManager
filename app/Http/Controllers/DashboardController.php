<?php

namespace App\Http\Controllers;

use App\Enums\AnimalStatus;
use App\Enums\OccupancyState;
use App\Enums\Species;
use App\Http\Resources\AnimalMovementResource;
use App\Http\Resources\PaddockResource;
use App\Models\Animal;
use App\Models\AnimalMovement;
use App\Models\Paddock;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RECENT_MOVEMENTS = 10;

    public function __invoke(): Response
    {
        $paddocks = Paddock::query()->withOccupancy()->orderBy('name')->get();

        $needingAttention = $paddocks->filter(
            fn (Paddock $paddock) => $paddock->occupancyState() !== OccupancyState::Ok,
        )->sortByDesc(fn (Paddock $paddock) => $paddock->occupancyRatio());

        $recentMovements = AnimalMovement::query()
            ->with(['animal', 'fromPaddock', 'toPaddock'])
            ->latest('moved_at')
            ->latest('id')
            ->limit(self::RECENT_MOVEMENTS)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'active_animals' => Animal::query()->active()->count(),
                'unplaced_animals' => Animal::query()->active()->whereNull('current_paddock_id')->count(),
                'paddocks' => $paddocks->count(),
                'total_capacity' => $paddocks->sum('capacity'),
                'total_occupancy' => $paddocks->sum('occupancy'),
                'paddocks_needing_attention' => $needingAttention->count(),
            ],
            'animalsBySpecies' => $this->animalsBySpecies(),
            'animalsByStatus' => $this->animalsByStatus(),
            'paddocks' => PaddockResource::collection($paddocks)->resolve(),
            'attention' => PaddockResource::collection($needingAttention->values())->resolve(),
            'recentMovements' => AnimalMovementResource::collection($recentMovements)->resolve(),
        ]);
    }

    /**
     * Active animals grouped by species, largest group first.
     *
     * @return array<int, array{species: string, label: string, count: int}>
     */
    private function animalsBySpecies(): array
    {
        $counts = Animal::query()
            ->active()
            ->selectRaw('species, count(*) as aggregate')
            ->groupBy('species')
            ->pluck('aggregate', 'species');

        return Collection::make(Species::cases())
            ->map(fn (Species $species) => [
                'species' => $species->value,
                'label' => $species->label(),
                'count' => (int) ($counts[$species->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{status: string, label: string, count: int}>
     */
    private function animalsByStatus(): array
    {
        $counts = Animal::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return Collection::make(AnimalStatus::cases())
            ->map(fn (AnimalStatus $status) => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }
}
