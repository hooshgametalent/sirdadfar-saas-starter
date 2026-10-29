<?php

namespace App\Models;

use App\Enums\DriverVerificationStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'mobile',
        'email',
        'role',
        'is_active',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'shipper',
        'is_active' => true,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasRole(...UserRole::panelRoles());
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isShipper(): bool
    {
        return $this->role === UserRole::Shipper;
    }

    public function isDriver(): bool
    {
        return $this->role === UserRole::Driver;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Operators and admins run day to day order operations.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Operator);
    }

    public function canManageContent(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::ContentEditor);
    }

    public function canModerateReviews(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Operator, UserRole::ContentEditor);
    }

    public function isVerifiedDriver(): bool
    {
        return $this->isDriver()
            && $this->driverProfile?->verification_status === DriverVerificationStatus::Approved;
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function shipperOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipper_id');
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_driver_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'driver_id');
    }

    public function scopeRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role', $role->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
