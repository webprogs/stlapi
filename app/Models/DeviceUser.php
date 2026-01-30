<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceUser extends Model
{
    protected $fillable = [
        'device_id',
        'local_id',
        'username',
        'role',
        'name',
        'device_created_at',
    ];

    protected $casts = [
        'device_created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
