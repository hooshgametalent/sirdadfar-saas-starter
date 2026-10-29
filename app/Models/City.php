<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'province_id',
        'name',
        'slug',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
