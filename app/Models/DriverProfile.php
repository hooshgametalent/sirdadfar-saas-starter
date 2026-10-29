<?php

namespace App\Models;

use App\Enums\DriverVerificationStatus;
use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverProfile extends Model
{
    /** @use HasFactory<DriverProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'national_code',
        'vehicle_type_id',
        'plate_number',
        'vehicle_model',
        'capacity_kg',
        'home_city_id',
        'verification_status',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => 'pending',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function homeCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'home_city_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === DriverVerificationStatus::Approved;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => DriverVerificationStatus::class,
            'verified_at' => 'datetime',
            'capacity_kg' => 'integer',
        ];
    }
}
