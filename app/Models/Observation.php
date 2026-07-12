<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\ObservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id',
    'species',
    'observed_at',
    'latitude',
    'longitude',
    'location_name',
    'description',
    'water_temperature',
    'depth_meters',
    'weather',
    'published_at',
])]
class Observation extends Model
{
    /** @use HasFactory<ObservationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'water_temperature' => 'decimal:1',
            'depth_meters' => 'decimal:1',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the user who created this observation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all media resources for this observation.
     *
     * @return MorphMany<resource, $this>
     */
    public function resources(): MorphMany
    {
        return $this->morphMany(Resource::class, 'resourceable');
    }

    /**
     * Get photo resources for this observation.
     *
     * @return MorphMany<resource, $this>
     */
    public function photos(): MorphMany
    {
        return $this->resources()->where('type', ResourceType::Photo->value);
    }

    /**
     * Get video resources for this observation.
     *
     * @return MorphMany<resource, $this>
     */
    public function videos(): MorphMany
    {
        return $this->resources()->where('type', ResourceType::Video->value);
    }

    /**
     * Scope to only published observations.
     *
     * @param  Builder<Observation>  $query
     * @return Builder<Observation>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
