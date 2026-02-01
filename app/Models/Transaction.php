<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'device_id',
        'local_user_id',
        'game_type',
        'numbers',
        'amount',
        'draw_time',
        'draw_date',
        'status',
        'win_amount',
        'claimed_at',
        'local_created_at',
    ];

    protected function casts(): array
    {
        return [
            'numbers' => 'array',
            'amount' => 'decimal:2',
            'win_amount' => 'decimal:2',
            'draw_date' => 'date',
            'claimed_at' => 'datetime',
            'local_created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function calculateWinAmount(): float
    {
        $config = config('stl.games.' . $this->game_type);
        if (!$config) {
            return 0;
        }
        return $this->amount * $config['multiplier'];
    }

    public function markAsWon(): void
    {
        $this->status = 'won';
        $this->win_amount = $this->calculateWinAmount();
        $this->save();
    }

    public function markAsLost(): void
    {
        $this->status = 'lost';
        $this->win_amount = null;
        $this->save();
    }

    public function markAsClaimed(): void
    {
        $this->status = 'claimed';
        $this->claimed_at = now();
        $this->save();
    }
}
