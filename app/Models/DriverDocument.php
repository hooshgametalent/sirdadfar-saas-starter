<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_profile_id',
        'type',
        'path',
        'original_name',
        'mime',
        'size',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'path',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => ReviewStatus::class,
            'size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
