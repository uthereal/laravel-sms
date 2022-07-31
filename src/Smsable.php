<?php

namespace Trapstats\Sms;

use Arr;
use DateInterval;
use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;

class Smsable implements SmsableContract
{
    use Conditionable, ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * The sender of the sms.
     *
     * @var string
     */
    public string $from = '';

    /**
     * The recipients of the sms.
     *
     * @var array
     */
    public array $to = [];

    /**
     * The content of the sms.
     *
     * @var string
     */
    public string $content = "";

    /**
     * List of callbacks for the sms
     *
     * @var callable[]
     */
    public array $callbacks = [];

    /**
     * The name of the messenger that should send this sms.
     *
     * @var string|null
     */
    public ?string $messenger = null;

    /**
     * @inheritDoc
     */
    public function send(SmsFactory|MessengerContract $messenger): ?SentMessage
    {
        if (method_exists($this, 'build')) {
            Container::getInstance()->call([$this, 'build']);
        }

        $messenger = $messenger instanceof SmsFactory
            ? $messenger->messenger($this->messenger)
            : $messenger;

        return $messenger->send($this->content, function (Sms $message) {
            $message->from($this->from)
                ->to($this->to);
        });
    }

    /**
     * @inheritDoc
     */
    public function queue(Queue $queue): mixed
    {
        if (isset($this->delay)) {
            return $this->later($this->delay, $queue);
        }

        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->pushOn(
            $queueName ?: null, $this->newQueuedJob()
        );
    }

    /**
     * @inheritDoc
     */
    public function later(DateTimeInterface|DateInterval|int $delay, Queue $queue): mixed
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->laterOn(
            $queueName ?: null, $delay, $this->newQueuedJob()
        );
    }

    /**
     * Make the queued sms job instance.
     *
     * @return \Trapstats\Sms\SendQueuedSmsable
     */
    protected function newQueuedJob(): SendQueuedSmsable
    {
        return (new SendQueuedSmsable($this))
            ->through(array_merge(
                method_exists($this, 'middleware') ? $this->middleware() : [],
                $this->middleware ?? []
            ));
    }

    /**
     * Set the sender of the sms.
     *
     * @param  string  $phoneNumber
     * @return \Trapstats\Sms\Smsable
     */
    public function from(string $phoneNumber): self
    {
        $this->from = $phoneNumber;

        return $this;
    }

    /**
     * Determine if the given sender is the sender of the smsable.
     *
     * @param  string  $phoneNumber
     * @return bool
     */
    public function isFrom(string $phoneNumber): bool
    {
        return $this->from === $phoneNumber;
    }

    /**
     * @inheritDoc
     */
    public function to(string|array $phoneNumbers): self
    {
        $this->to = Arr::wrap($phoneNumbers);

        return $this;
    }

    /**
     * Determine if the given recipient(s) is set on the smsable.
     *
     * @param  string|array  $phoneNumbers
     * @return bool
     */
    public function hasTo(string|array $phoneNumbers): bool
    {
        foreach (Arr::wrap($phoneNumbers) as $phoneNumber) {
            if (!in_array($phoneNumber, $this->to)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the content of the smsable.
     *
     * @param  string  $content
     * @return $this
     */
    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Assert that the given text is present in the content of the smsable
     *
     * @param  string  $string
     * @return $this
     */
    public function assertSeeInText(string $string): self
    {
        PHPUnit::assertTrue(
            str_contains($this->content, $string),
            "Did not see expected text [{$string}] within text email body."
        );

        return $this;
    }

    /**
     * Assert that the given text is not present in the content of the smsable
     *
     * @param  string  $string
     * @return $this
     */
    public function assertDontSeeInText(string $string): self
    {
        PHPUnit::assertFalse(
            str_contains($this->content, $string),
            "Saw unexpected text [{$string}] within text email body."
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function messenger(?string $messenger): self
    {
        $this->messenger = $messenger;

        return $this;
    }

    /**
     * Register a callback to be called with the sms instance
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withMessage(callable $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Dynamically bind parameters to the message.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters): self
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        static::throwBadMethodCallException($method);
    }
}