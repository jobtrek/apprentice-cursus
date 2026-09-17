<?php

namespace App\Providers;

use App\Services\Microsoft\AzureGraphService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class RiakServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AzureGraphService::class, function (Application $app) {
            return new AzureGraphService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}