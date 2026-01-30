<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [
        'device_id',
        'api_key',
        'device_name',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    public static function generateApiKey(): string
    {
        return Str::random(64);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function drawResults(): HasMany
    {
        return $this->hasMany(DrawResult::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(DeviceUser::class);
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function getTotalEarnings(): float
    {
        return $this->transactions()->sum('amount');
    }

    public function getTodayEarnings(): float
    {
        return $this->transactions()
            ->whereDate('created_at', today())
            ->sum('amount');
    }
}
