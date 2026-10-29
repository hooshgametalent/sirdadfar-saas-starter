<?php

namespace App\Models;

use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manually recorded settlement. No online payment gateway is involved.
 */
class Payment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'direction',
        'amount',
        'status',
        'reference_note',
        'recorded_by',
        'paid_at',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => PaymentDirection::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
