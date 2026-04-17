<?php

namespace App\Providers;

use App\Contracts\PublishesDeletionNotifications;
use App\Services\RabbitMqDeletionNotificationPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PublishesDeletionNotifications::class,
            RabbitMqDeletionNotificationPublisher::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
