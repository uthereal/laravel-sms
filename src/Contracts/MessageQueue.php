<?php

namespace Trapstats\Sms\Contracts;

use DateInterval;
use DateTimeInterface;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;

interface MessageQueue
{
    /**
     * Queue a new sms for sending.
     *
     * @param  \Trapstats\Sms\Contracts\Smsable  $sms
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue(SmsableContract $sms, ?string $queue = null): mixed;

    /**
     * Queue a new sms for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Trapstats\Sms\Contracts\Smsable  $sms
     * @param  string|null  $queue
     * @return mixed
     */
    public function later(DateTimeInterface|DateInterval|int $delay, SmsableContract $sms, ?string $queue = null): mixed;
}