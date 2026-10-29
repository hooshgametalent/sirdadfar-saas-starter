<?php

namespace App\Models;

use App\Enums\PageType;
use App\Enums\PublishStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'type',
        'origin_city_id',
        'destination_city_id',
        'body',
        'status',
        'seo_title',
        'seo_description',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'static',
        'status' => 'draft',
    ];

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value);
    }

    public function scopeOfType(Builder $query, PageType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PublishStatus::class,
        ];
    }
}
