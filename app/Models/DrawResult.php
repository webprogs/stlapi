<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawResult extends Model
{
    protected $fillable = [
        'device_id',
        'local_id',
        'draw_date',
        'draw_time',
        'game_type',
        'winning_numbers',
        'device_created_at',
    ];

    protected $casts = [
        'winning_numbers' => 'array',
        'draw_date' => 'date',
        'device_created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Check if this is an admin-created result
     */
    public function isAdminCreated(): bool
    {
        return is_null($this->device_id);
    }

    /**
     * Scope for admin-created results
     */
    public function scopeAdminCreated($query)
    {
        return $query->whereNull('device_id');
    }

    /**
     * Scope for device-synced results
     */
    public function scopeDeviceSynced($query)
    {
        return $query->whereNotNull('device_id');
    }
}
