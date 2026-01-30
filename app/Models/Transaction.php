<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'device_id',
        'local_id',
        'transaction_id',
        'user_id',
        'amount',
        'numbers',
        'game_type',
        'draw_date',
        'draw_time',
        'payment_method',
        'verified',
        'device_created_at',
    ];

    protected $casts = [
        'numbers' => 'array',
        'amount' => 'decimal:2',
        'verified' => 'boolean',
        'draw_date' => 'date',
        'device_created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
