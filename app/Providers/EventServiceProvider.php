<?php

namespace App\Providers;

use App\Events\UserActivityEvent;
use App\Listeners\LogUserActivity;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserActivityEvent::class => [
            LogUserActivity::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}