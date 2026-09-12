<?php

namespace App\Models;

use App\Enums\OccupancyState;
use Database\Factories\PaddockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $capacity
 * @property string|null $notes
 * @property int $occupancy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'capacity', 'notes'])]
class Paddock extends Model
{
    /** @use HasFactory<PaddockFactory> */
    use HasFactory;

    /**
     * Animals whose current paddock is this one (any status).
     *
     * @return HasMany<Animal, $this>
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'current_paddock_id');
    }

    /**
     * Animals that currently occupy capacity in this paddock.
     *
     * @return HasMany<Animal, $this>
     */
    public function activeAnimals(): HasMany
    {
        return $this->animals()->active();
    }

    /**
     * Eager-load the occupancy count so list pages avoid N+1 queries.
     *
     * @param  Builder<Paddock>  $query
     * @return Builder<Paddock>
     */
    #[Scope]
    protected function withOccupancy(Builder $query): Builder
    {
        return $query->withCount(['activeAnimals as occupancy']);
    }

    /**
     * Number of active animals in the paddock. Uses the eager-loaded
     * count when present, otherwise falls back to a query.
     */
    protected function occupancy(): Attribute
    {
        return Attribute::get(
            fn (?int $value): int => $value ?? $this->activeAnimals()->count(),
        );
    }

    public function occupancyRatio(): float
    {
        return $this->capacity > 0 ? $this->occupancy / $this->capacity : 1.0;
    }

    public function occupancyState(): OccupancyState
    {
        return OccupancyState::fromRatio($this->occupancyRatio());
    }

    public function hasRoom(): bool
    {
        return $this->occupancy < $this->capacity;
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->capacity - $this->occupancy);
    }
}
