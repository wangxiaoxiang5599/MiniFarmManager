<?php

namespace App\Models;

use Database\Factories\AnimalMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single relocation of an animal. A null "from" means the animal was
 * placed for the first time; a null "to" means it left its paddock.
 *
 * @property int $id
 * @property int $animal_id
 * @property int|null $from_paddock_id
 * @property int|null $to_paddock_id
 * @property Carbon $moved_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['animal_id', 'from_paddock_id', 'to_paddock_id', 'moved_at', 'notes'])]
class AnimalMovement extends Model
{
    /** @use HasFactory<AnimalMovementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Animal, $this>
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /**
     * @return BelongsTo<Paddock, $this>
     */
    public function fromPaddock(): BelongsTo
    {
        return $this->belongsTo(Paddock::class, 'from_paddock_id');
    }

    /**
     * @return BelongsTo<Paddock, $this>
     */
    public function toPaddock(): BelongsTo
    {
        return $this->belongsTo(Paddock::class, 'to_paddock_id');
    }
}
