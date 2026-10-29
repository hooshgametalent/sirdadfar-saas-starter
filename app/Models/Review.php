<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'shipper_id',
        'driver_id',
        'rating',
        'body',
        'publish_consent',
        'display_name',
        'status',
        'is_featured',
        'admin_reply',
        'moderated_by',
        'moderated_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'publish_consent' => false,
        'is_featured' => false,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Reviews that may be shown publicly: approved by a moderator and
     * explicitly consented to by the shipper.
     */
    public function scopePublishable(Builder $query): Builder
    {
        return $query
            ->where('status', ReviewStatus::Approved->value)
            ->where('publish_consent', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'rating' => 'integer',
            'publish_consent' => 'boolean',
            'is_featured' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }
}
