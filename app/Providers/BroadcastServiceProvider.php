<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Echo private channels auth. Must be Sanctum Bearer (same as /api/v1/calling),
        // not the default web+session stack. Register both URL shapes the SPA may call:
        //   POST /broadcasting/auth
        //   POST /api/broadcasting/auth   (when API base URL already includes /api)
        $attributes = ['middleware' => ['api', 'auth:sanctum']];

        Broadcast::routes($attributes);
        Broadcast::routes($attributes + ['prefix' => 'api']);

        require base_path('routes/channels.php');
    }
}
