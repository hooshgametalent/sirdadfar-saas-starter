<?php

namespace App\Models;

use App\Enums\DriverVerificationStatus;
use App\Enums\OfferStatus;
use App\Enums\UserRole;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'driver_id',
        'price',
        'message',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::Pending->value);
    }

    /**
     * Offers whose driver is still active and verified, so the offer can
     * still be accepted. Mirrors the checks in OfferService::submit().
     */
    public function scopeFromEligibleDrivers(Builder $query): Builder
    {
        return $query->whereHas('driver', fn (Builder $driver): Builder => $driver
            ->where('is_active', true)
            ->where('role', UserRole::Driver->value)
            ->whereHas('driverProfile', fn (Builder $profile): Builder => $profile
                ->where('verification_status', DriverVerificationStatus::Approved->value)));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'price' => 'integer',
        ];
    }
}
