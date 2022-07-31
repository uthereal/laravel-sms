<?php

namespace Trapstats\Sms;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms.php', 'sms');

        $this->registerMessenger();
    }

    /**
     * Register the messenger instance.
     *
     * @return void
     */
    protected function registerMessenger(): void
    {
        $this->app->singleton('sms.manager', function (Application $app) {
            return new SmsManager($app);
        });

        $this->app->bind('messenger', function (Application $app) {
            return $app->make('sms.manager')->messenger();
        });
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->runningInConsole() ? $this->publishConfig() : null;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'sms.manager',
            'messenger',
        ];
    }

    /**
     * Publish the config to user space.
     *
     * @return void
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/sms.php' => config_path('sms.php'),
        ], 'sms-config');
    }
}

