<?php

namespace Trapstats\Sms\Channels;

use Illuminate\Notifications\Notification;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;
use Trapstats\Sms\Sms;

class SmsChannel
{
    /**
     * Create a new sms channel instance.
     *
     * @param  \Trapstats\Sms\Contracts\Factory  $messenger
     */
    public function __construct(
        protected SmsFactory $messenger
    ) {
        //...
    }

    /**
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        /** @var \Trapstats\Sms\Contracts\Smsable|string $message */
        $message = $notification->toSms($notifiable);

        if (!$notifiable->routeNotificationFor('sms', $notification) && !$message instanceof SmsableContract) {
            return;
        }

        if ($message instanceof SmsableContract) {
            $message->send($this->messenger);

            return;
        }

        $this->messenger->messenger($message->messenger ?? null)->send(
            $message,
            function (Sms $sms) use ($notifiable, $notification, $message) {
                $sms->to($notifiable->routeNotificationFor('sms', $notification));
            }
        );
    }
}