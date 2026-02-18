<?php

namespace Ahmokhan1\MetaAds;

use Illuminate\Support\ServiceProvider;
use Ahmokhan1\MetaAds\Commands\SyncMetaAdsMetrics;

class MetaAdsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/meta_ads.php', 'meta_ads');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/meta_ads.php' => config_path('meta_ads.php'),
        ], 'meta-ads-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'meta-ads-migrations');

        $this->publishes([
            __DIR__ . '/../stubs/Models/Lead.php' => app_path('Models/Lead.php'),
        ], 'meta-ads-models');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncMetaAdsMetrics::class,
            ]);
        }
    }
}


