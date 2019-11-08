<?php

namespace VanOns\Laraberg;

use Illuminate\Support\ServiceProvider;

class LarabergServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(\Request $request)
    {
        if (config('laraberg.use_package_routes')) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->loadTranslationsFrom( __DIR__ . '/resources/lang', 'laraberg' );

        $this->publishes([__DIR__.'/../resources/lang' => resource_path('lang/vendor/laraberg')], 'laraberg-i18n');
        $this->publishes([__DIR__ . '/../public' => public_path('vendor/laraberg')], 'laraberg-assets');
        $this->publishes([__DIR__ . '/config/laraberg.php' => config_path('laraberg.php')], 'laraberg-config');
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Laraberg::class, function () {
            return new Laraberg();
        });

        $this->app->alias(Laraberg::class, 'laraberg');
    }
}
