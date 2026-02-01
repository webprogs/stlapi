<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'device_name',
        'api_key',
        'is_active',
        'last_sync_at',
        'last_ip',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->uuid)) {
                $device->uuid = (string) Str::uuid();
            }
            if (empty($device->api_key)) {
                $device->api_key = Str::random(64);
            }
        });
    }

    public function deviceUsers(): HasMany
    {
        return $this->hasMany(DeviceUser::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function regenerateApiKey(): string
    {
        $this->api_key = Str::random(64);
        $this->save();
        return $this->api_key;
    }
}
