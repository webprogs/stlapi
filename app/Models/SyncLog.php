<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'sync_type',
        'records_synced',
        'status',
        'error_message',
        'ip_address',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
