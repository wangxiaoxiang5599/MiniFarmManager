<?php

namespace App\Models;

use App\Enums\AnimalStatus;
use App\Enums\Sex;
use App\Enums\Species;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $tag_number
 * @property string|null $name
 * @property Species $species
 * @property Sex $sex
 * @property Carbon $date_of_birth
 * @property string|null $breed
 * @property AnimalStatus $status
 * @property string|null $notes
 * @property int|null $current_paddock_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tag_number', 'name', 'species', 'sex', 'date_of_birth', 'breed', 'status', 'notes'])]
class Animal extends Model
{
    /** @use HasFactory<AnimalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species' => Species::class,
            'sex' => Sex::class,
            'status' => AnimalStatus::class,
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Paddock, $this>
     */
    public function currentPaddock(): BelongsTo
    {
        return $this->belongsTo(Paddock::class, 'current_paddock_id');
    }

    /**
     * @return HasMany<AnimalMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(AnimalMovement::class);
    }

    /**
     * @return HasMany<HealthRecord, $this>
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    /**
     * @param  Builder<Animal>  $query
     * @return Builder<Animal>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', AnimalStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === AnimalStatus::Active;
    }

    /**
     * Tag number, with the name appended when the animal has one.
     */
    public function displayName(): string
    {
        return $this->name ? "{$this->tag_number} ({$this->name})" : $this->tag_number;
    }
}
