<?php

namespace App\Models;

use App\Events\DrawResultCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'draw_date',
        'draw_time',
        'game_type',
        'winning_numbers',
        'set_by',
        'is_official',
        'modified_at',
    ];

    protected function casts(): array
    {
        return [
            'winning_numbers' => 'array',
            'draw_date' => 'date',
            'is_official' => 'boolean',
            'modified_at' => 'datetime',
        ];
    }

    protected $dispatchesEvents = [
        'created' => DrawResultCreated::class,
    ];

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
