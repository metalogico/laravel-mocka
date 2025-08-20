<?php

namespace Metalogico\Mocka\Providers;

use Illuminate\Support\ServiceProvider;
use Metalogico\Mocka\Http\MockaFactory;
use Metalogico\Mocka\Console\MockaListCommand;

class MockaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mocka.php', 'mocka');

        // Register Mocka HTTP factory as a singleton
        // Illuminate\Http\Client\Factory expects a Dispatcher (events) as first arg
        $this->app->singleton('mocka.http', function ($app) {
            return new MockaFactory($app['events'] ?? null);
        });

        // Alias for type-hinting
        $this->app->alias('mocka.http', MockaFactory::class);
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/mocka.php' => config_path('mocka.php'),
        ], 'mocka-config');

        // Register Artisan commands when running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                MockaListCommand::class,
            ]);
        }
    }
}
