<?php

namespace App\Events;

use App\Models\DrawResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawResultCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DrawResult $drawResult
    ) {}
}
