<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'resourceable_id',
    'resourceable_type',
    'type',
    'path',
    'mime_type',
    'size_bytes',
    'sort_order',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the parent resourceable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function resourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
