<?php

namespace Trapstats\Sms\Contracts;

use Trapstats\Sms\Contracts\Smsable as SmsableContract;
use Trapstats\Sms\PendingSms;
use Trapstats\Sms\SentMessage;

interface Messenger
{
    /**
     * Begin the process of sending a sms.
     *
     * @param  string|array  $to
     * @return \Trapstats\Sms\PendingSms
     */
    public function to(string|array $to): PendingSms;

    /**
     * Send text or a smsable.
     *
     * @param  \Trapstats\Sms\Contracts\Smsable|string  $sms
     * @param  callable|null  $callback
     * @return \Trapstats\Sms\SentMessage|null
     */
    public function send(SmsableContract|string $sms, ?callable $callback = null): ?SentMessage;
}