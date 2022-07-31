<?php

namespace Trapstats\Sms;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Trapstats\Sms\Contracts\MessageQueue as MessengerQueueContract;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\Events\MessageSending;
use Trapstats\Sms\Events\MessageSent;

class Messenger implements MessengerContract, MessengerQueueContract
{
    use Macroable;
    use Conditionable;

    /**
     * The global from number.
     *
     * @var string|null
     */
    protected ?string $from = null;

    /**
     * The global to number.
     *
     * @var string|null
     */
    protected ?string $to = null;

    /**
     * Create a new messenger instance
     *
     * @param  string  $name
     * @param  \Trapstats\Sms\Contracts\TransportContract  $transport
     * @param  \Illuminate\Events\Dispatcher|null  $events
     * @param  \Illuminate\Contracts\Queue\Factory|null  $queue
     */
    public function __construct(
        protected readonly string $name,
        protected readonly TransportContract $transport,
        protected readonly ?Dispatcher $events = null,
        protected readonly ?QueueContract $queue = null,
    ) {
        //...
    }

    /**
     * Set the global from phone number
     *
     * @param  string  $number
     * @return void
     */
    public function alwaysFrom(string $number): void
    {
        $this->from = $number;
    }

    /**
     * Set the global to phone number
     *
     * @param  string  $number
     * @return void
     */
    public function alwaysTo(string $number): void
    {
        $this->to = $number;
    }

    /**
     * @inheritDoc
     */
    public function to(string|array $to): PendingSms
    {
        return (new PendingSms($this))->to($to);
    }

    /**
     * @inheritDoc
     */
    public function send(SmsableContract|string $sms, ?callable $callback = null): ?SentMessage
    {
        if ($sms instanceof SmsableContract) {
            return $this->sendSmsable($sms);
        }

        // Create a sms message instance
        $message = new Sms();

        if (!is_null($callback)) {
            $callback($message);
        }

        // Set the content of the message
        $message->content($sms);

        // Set global addresses
        $this->when($this->to, fn() => $message->to($this->to));
        $this->when($this->from, fn() => $message->from($this->from));

        if ($this->shouldSendMessage($message)) {
            if ($sentMessage = $this->transport->send($message)) {
                $this->dispatchSentEvent($sentMessage);

                return $sentMessage;
            }
        }

        return null;
    }

    /**
     * Send the given smsable
     *
     * @param  \Trapstats\Sms\Contracts\Smsable  $sms
     * @return \Trapstats\Sms\SentMessage|null
     */
    public function sendSmsable(SmsableContract $sms): ?SentMessage
    {
        $messenger = $sms->messenger($this->name);

        return $sms instanceof ShouldQueue
            ? $messenger->queue($this->queue)
            : $messenger->send($this);
    }

    /**
     * @inheritDoc
     */
    public function queue(SmsableContract $sms, ?string $queue = null): mixed
    {
        if (is_string($queue) && $sms instanceof ShouldQueue) {
            $sms->onQueue($queue);
        }

        return $sms->messenger($this->name)->queue($this->queue);
    }

    /**
     * @inheritDoc
     */
    public function later(DateTimeInterface|DateInterval|int $delay, SmsableContract $sms, ?string $queue = null): mixed
    {
        return $sms->messenger($this->name)->later(
            $delay, is_null($queue) ? $this->queue : $queue
        );
    }

    /**
     * Determines if the sms can be sent.
     *
     * @param  \Trapstats\Sms\Sms  $sms
     * @return bool
     */
    protected function shouldSendMessage(Sms $sms): bool
    {
        if (!$this->events) {
            return true;
        }

        return $this->events->until(
                new MessageSending($sms)
            ) !== false;
    }

    /**
     * Dispatch the message sent event.
     *
     * @param  \Trapstats\Sms\SentMessage  $sentMessage
     * @return void
     */
    protected function dispatchSentEvent(SentMessage $sentMessage): void
    {
        $this->events?->dispatch(new MessageSent($sentMessage));
    }
}