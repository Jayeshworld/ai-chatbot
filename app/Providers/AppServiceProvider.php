<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\ConversationService;
use App\Services\OllamaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OllamaService::class);
        $this->app->singleton(ConversationService::class);
        $this->app->singleton(AnalyticsService::class);
    }

    public function boot(): void
    {
        //
    }
}
