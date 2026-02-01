<?php

namespace App\Providers;

use App\Events\DrawResultCreated;
use App\Listeners\UpdateTransactionStatuses;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(
            DrawResultCreated::class,
            UpdateTransactionStatuses::class,
        );
    }
}
