<?php

namespace Trapstats\Sms;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Trapstats\Sms\Channels\SmsChannel;

class SmsNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(): void
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('sms', function (Application $app) {
                return new SmsChannel(
                    $app->make('sms.manager')
                );
            });
        });
    }
}

