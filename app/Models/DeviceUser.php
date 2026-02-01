<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'local_user_id',
        'name',
        'pin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = [
        'pin',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
