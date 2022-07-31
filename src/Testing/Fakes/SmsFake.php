<?php

namespace Trapstats\Sms\Testing\Fakes;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\MessageQueue as MessengerQueueContract;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;
use Trapstats\Sms\SentMessage;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;

class SmsFake implements SmsFactory, MessengerContract, MessengerQueueContract
{
    use ReflectsClosures;

    /**
     * The messenger currently being used to send a message.
     *
     * @var string|null
     */
    protected ?string $currentMessenger;

    /**
     * The text messages that have been sent.
     *
     * @var \Trapstats\Sms\Contracts\Smsable[]
     */
    protected array $sentSms = [];

    /**
     * The text messages that have been queued.
     *
     * @var \Trapstats\Sms\Contracts\Smsable[]
     */
    protected array $queuedSms = [];

    /**
     * Assert if a sms was sent based on a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|int|null  $callback
     * @return void
     * @throws \ReflectionException
     */
    public function assertSent(string|callable $sms, callable|int|null $callback = null): void
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        if (is_numeric($callback)) {
            $this->assertSentTimes($sms, $callback);

            return;
        }

        $message = "The expected [{$sms}] sms was not sent.";

        if (count($this->queuedSms) > 0) {
            $message .= ' Did you mean to use assertQueued() instead?';
        }

        PHPUnit::assertTrue(
            $this->sent($sms, $callback)->count() > 0,
            $message
        );
    }

    /**
     * Assert if a sms was sent a number of times.
     *
     * @param  string  $sms
     * @param  int  $times
     * @return void
     * @throws \ReflectionException
     */
    protected function assertSentTimes(string $sms, int $times = 1): void
    {
        $count = $this->sent($sms)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$sms}] sms was sent {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a sms was not sent or queued to be sent based on a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|int|null  $callback
     * @return void
     * @throws \ReflectionException
     */
    public function assertNotOutgoing(string|callable $sms, callable|int|null $callback = null): void
    {
        $this->assertNotSent($sms, $callback);
        $this->assertNotQueued($sms, $callback);
    }

    /**
     * Determine if a sms was not sent based on a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|int|null  $callback
     * @return void
     * @throws \ReflectionException
     */
    public function assertNotSent(string|callable $sms, callable|int|null $callback = null): void
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        PHPUnit::assertCount(
            0, $this->sent($sms, $callback),
            "The unexpected [{$sms}] sms was sent."
        );
    }

    /**
     * Assert that no sms messages were sent or queued to be sent.
     *
     * @return void
     */
    public function assertNothingOutgoing(): void
    {
        $this->assertNothingSent();
        $this->assertNothingQueued();
    }

    /**
     * Assert that no sms messages were sent.
     *
     * @return void
     */
    public function assertNothingSent(): void
    {
        $smsNames = collect($this->sentSms)
            ->map(fn($sms) => get_class($sms))
            ->join(', ');

        PHPUnit::assertEmpty($this->sentSms, 'The following sms messages were sent unexpectedly: '.$smsNames);
    }

    /**
     * Assert if a sms was queued based on a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|int|null  $callback
     * @return void
     * @throws \ReflectionException
     */
    public function assertQueued(string|callable $sms, callable|int|null $callback = null): void
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        if (is_numeric($callback)) {
            $this->assertQueuedTimes($sms, $callback);

            return;
        }

        PHPUnit::assertTrue(
            $this->queued($sms, $callback)->count() > 0,
            "The expected [{$sms}] sms was not queued."
        );
    }

    /**
     * Assert if a sms was queued a number of times.
     *
     * @param  string  $sms
     * @param  int  $times
     * @return void
     * @throws \ReflectionException
     */
    protected function assertQueuedTimes(string $sms, int $times = 1): void
    {
        $count = $this->queued($sms)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$sms}] sms was queued {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a sms was not queued based on a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|null  $callback
     * @return void
     * @throws \ReflectionException
     */
    public function assertNotQueued(string|callable $sms, ?callable $callback = null): void
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        PHPUnit::assertCount(
            0, $this->queued($sms, $callback),
            "The unexpected [{$sms}] sms was queued."
        );
    }

    /**
     * Assert that no sms messages were queued.
     *
     * @return void
     */
    public function assertNothingQueued(): void
    {
        $smsNames = collect($this->queuedSms)
            ->map(fn($sms) => get_class($sms))
            ->join(', ');

        PHPUnit::assertEmpty($this->queuedSms, 'The following smss were queued unexpectedly: '.$smsNames);
    }

    /**
     * Get the sms messages matching a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    public function sent(string|callable $sms, ?callable $callback = null): Collection
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        if (!$this->hasSent($sms)) {
            return collect();
        }

        $callback = $callback ?: fn() => true;

        return $this->smsOf($sms)->filter(fn($sms) => $callback($sms));
    }

    /**
     * Determine if the given sms has been sent.
     *
     * @param  string  $sms
     * @return bool
     */
    public function hasSent(string $sms): bool
    {
        return $this->smsOf($sms)->count() > 0;
    }

    /**
     * Get the queued sms messages matching a truth-test callback.
     *
     * @param  string|callable  $sms
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    public function queued(string|callable $sms, ?callable $callback = null): Collection
    {
        [$sms, $callback] = $this->prepareSmsAndCallback($sms, $callback);

        if (!$this->hasQueued($sms)) {
            return collect();
        }

        $callback = $callback ?: fn() => true;

        return $this->queuedSmsOf($sms)->filter(fn($sms) => $callback($sms));
    }

    /**
     * Determine if the given sms has been queued.
     *
     * @param  string  $sms
     * @return bool
     */
    public function hasQueued(string $sms): bool
    {
        return $this->queuedSmsOf($sms)->count() > 0;
    }

    /**
     * Get the sms messages for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function smsOf(string $type): Collection
    {
        return collect($this->sentSms)->filter(fn($sms) => $sms instanceof $type);
    }

    /**
     * Get the queued sms messages for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function queuedSmsOf(string $type): Collection
    {
        return collect($this->queuedSms)->filter(fn($sms) => $sms instanceof $type);
    }

    /**
     * @inheritDoc
     */
    public function messenger(?string $name = null): MessengerContract
    {
        $this->currentMessenger = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function to(string|array $to): PendingSmsFake
    {
        return (new PendingSmsFake($this))->to($to);
    }

    /**
     * @inheritDoc
     */
    public function send(SmsableContract|string $sms, ?callable $callback = null): ?SentMessage
    {
        if($sms instanceof SmsableContract) {
            $sms->messenger($this->currentMessenger);
        }

        $this->currentMessenger = null;

        if ($sms instanceof ShouldQueue && $sms instanceof SmsableContract) {
            return $this->queue($sms);
        }

        $this->sentSms[] = $sms;

        return null;
    }

    /**
     * @inheritDoc
     */
    public function queue(SmsableContract $sms, ?string $queue = null): mixed
    {
        $this->currentMessenger = null;

        $this->queuedSms[] = $sms;

        return null;
    }

    /**
     * @inheritDoc
     */
    public function later(DateTimeInterface|DateInterval|int $delay, SmsableContract $sms, ?string $queue = null): mixed
    {
        return $this->queue($sms, $queue);
    }

    /**
     * Infer sms class using reflection if a typehinted closure is passed to assertion.
     *
     * @param  string|callable  $sms
     * @param  callable|null  $callback
     * @return array
     * @throws \ReflectionException
     */
    protected function prepareSmsAndCallback(string|callable $sms, ?callable $callback): array
    {
        if ($sms instanceof Closure) {
            return [$this->firstClosureParameterType($sms), $sms];
        }

        return [$sms, $callback];
    }

    /**
     * Forget the resolved messenger instances.
     *
     * @return $this
     */
    public function forgetMessengers(): self
    {
        $this->currentMessenger = null;

        return $this;
    }
}