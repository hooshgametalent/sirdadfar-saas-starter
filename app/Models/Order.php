<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Observers\OrderObserver;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Keys allowed in special_requirements with their Persian labels.
     *
     * @var array<string, string>
     */
    public const SPECIAL_REQUIREMENTS = [
        'loading_help' => 'نیاز به کارگر بارگیری',
        'insurance' => 'بیمه بار',
        'fragile' => 'بار حساس یا شکستنی',
        'covered' => 'نیاز به خودروی مسقف',
        'refrigerated' => 'نیاز به دمای کنترل‌شده',
    ];

    /**
     * Attributes a shipper may provide when submitting an order.
     *
     * @var list<string>
     */
    protected $fillable = [
        'origin_city_id',
        'destination_city_id',
        'origin_address',
        'destination_address',
        'cargo_type',
        'weight_kg',
        'volume_m3',
        'pickup_date',
        'pickup_window_start',
        'pickup_window_end',
        'vehicle_type_id',
        'special_requirements',
        'offered_price',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending_review',
    ];

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeOpenForOffers(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::OpenForOffers->value);
    }

    public function scopeForShipper(Builder $query, User $shipper): Builder
    {
        return $query->where('shipper_id', $shipper->getKey());
    }

    public function scopeAssignedTo(Builder $query, User $driver): Builder
    {
        return $query->where('assigned_driver_id', $driver->getKey());
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->shipper_id === (int) $user->getKey();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_driver_id !== null
            && (int) $this->assigned_driver_id === (int) $user->getKey();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'pickup_date' => 'date',
            'special_requirements' => 'array',
            'weight_kg' => 'integer',
            'volume_m3' => 'decimal:2',
            'offered_price' => 'integer',
            'final_price' => 'integer',
        ];
    }
}
