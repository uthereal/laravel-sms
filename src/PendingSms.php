<?php

namespace Trapstats\Sms;

use Arr;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;

class PendingSms
{
    use Conditionable;

    /**
     * The "to" recipients of the sms.
     *
     * @var array
     */
    protected array $to = [];

    /**
     * Create a new pending sms instance
     *
     * @param  \Trapstats\Sms\Contracts\Messenger  $messenger
     */
    public function __construct(
        protected readonly MessengerContract $messenger
    ) {
        //...
    }

    /**
     * Set the recipients of the sms
     *
     * @param  string|array  $to
     * @return $this
     */
    public function to(string|array $to): self
    {
        $this->to = Arr::wrap($to);

        return $this;
    }

    /**
     * Send a new sms message instance.
     *
     * @param  \Trapstats\Sms\Contracts\Smsable|string  $sms
     * @return \Trapstats\Sms\SentMessage|null
     */
    public function send(SmsableContract|string $sms): ?SentMessage
    {
        return $this->messenger->send($this->fill($sms));
    }

    /**
     * Push the sms onto the queue
     *
     * @param  \Trapstats\Sms\Contracts\Smsable|string  $sms
     * @return mixed
     */
    public function queue(SmsableContract|string $sms): mixed
    {
        return $this->messenger->queue($this->fill($sms));
    }

    /**
     * Deliver the queued message after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Trapstats\Sms\Contracts\Smsable|string  $sms
     * @return mixed
     */
    public function later(DateTimeInterface|DateInterval|int $delay, SmsableContract|string $sms): mixed
    {
        return $this->messenger->later($delay, $this->fill($sms));
    }

    /**
     * Populate the sms with the addresses.
     *
     * @param  \Trapstats\Sms\Contracts\Smsable|string  $sms
     * @return \Trapstats\Sms\Contracts\Smsable
     */
    protected function fill(SmsableContract|string $sms): SmsableContract
    {
        if (is_string($sms)) {
            $sms = (new Smsable())->content($sms);
        }

        return tap($sms, function (SmsableContract $sms) {
            $sms->to($this->to);
        });
    }
}