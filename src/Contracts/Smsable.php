<?php

namespace Trapstats\Sms\Contracts;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\Factory as Queue;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\SentMessage;

interface Smsable
{
    /**
     * Send the message using the given messenger.
     *
     * @param  \Trapstats\Sms\Contracts\Factory|\Trapstats\Sms\Contracts\Messenger  $messenger
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send(SmsFactory|MessengerContract $messenger): ?SentMessage;

    /**
     * Queue the given message.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue): mixed;

    /**
     * Deliver the queued message after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function later(DateTimeInterface|DateInterval|int $delay, Queue $queue): mixed;

    /**
     * Set the recipient(s) of the message.
     *
     * @param  string|array  $phoneNumbers
     * @return $this
     */
    public function to(string|array $phoneNumbers): self;

    /**
     * Set the name of the messenger that should be used to send the message.
     *
     * @param  string|null  $messenger
     * @return $this
     */
    public function messenger(?string $messenger): self;
}