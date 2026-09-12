<?php

namespace App\Models;

use App\Enums\HealthRecordType;
use Database\Factories\HealthRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $animal_id
 * @property Carbon $recorded_on
 * @property HealthRecordType $type
 * @property string $description
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['animal_id', 'recorded_on', 'type', 'description', 'notes'])]
class HealthRecord extends Model
{
    /** @use HasFactory<HealthRecordFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'type' => HealthRecordType::class,
        ];
    }

    /**
     * @return BelongsTo<Animal, $this>
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
