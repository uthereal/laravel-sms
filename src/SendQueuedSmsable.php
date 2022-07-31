<?php

namespace Trapstats\Sms;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\Smsable as SmsableContract;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Throwable;

class SendQueuedSmsable
{
    use Queueable;

    /**
     * The sms instance.
     *
     * @var \Trapstats\Sms\Contracts\Smsable|\Illuminate\Contracts\Queue\ShouldQueue
     */
    public SmsableContract|ShouldQueue $sms;

    /**
     * The number of times the job may be attempted
     *
     * @var int|null
     */
    public ?int $tries;

    /**
     * The number of seconds the job can run before timing out
     *
     * @var int|null
     */
    public ?int $timeout;

    /**
     * Indicates if the job should be encrypted
     *
     * @var bool
     */
    public bool $shouldBeEncrypted = false;

    /**
     * Create a new job instance.
     *
     * @param  \Trapstats\Sms\Contracts\Smsable  $sms
     */
    public function __construct(SmsableContract $sms)
    {
        $this->sms = $sms;
        $this->tries = property_exists($sms, 'tries') ? $sms->tries : null;
        $this->timeout = property_exists($sms, 'timeout') ? $sms->timeout : null;
        $this->afterCommit = property_exists($sms, 'afterCommit') ? $sms->afterCommit : null;
        $this->shouldBeEncrypted = $sms instanceof ShouldBeEncrypted;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Trapstats\Sms\Contracts\Factory  $factory
     * @return void
     */
    public function handle(SmsFactory $factory): void
    {
        $this->sms->send($factory);
    }

    /**
     * Get the number of seconds before a released message will be available.
     *
     * @return int|null
     */
    public function backoff(): ?int
    {
        if (!method_exists($this->sms, 'backoff') && !isset($this->sms->backoff)) {
            return null;
        }

        return $this->sms->backoff ?? $this->sms->backoff();
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime|null
     */
    public function retryUntil(): ?DateTime
    {
        if (!method_exists($this->sms, 'retryUntil') && !isset($this->sms->retryUntil)) {
            return null;
        }

        return $this->sms->retryUntil ?? $this->sms->retryUntil();
    }

    /**
     * Call the failed method on the message instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed(Throwable $e): void
    {
        if (method_exists($this->sms, 'failed')) {
            $this->sms->failed($e);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName(): string
    {
        return get_class($this->sms);
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone(): void
    {
        $this->sms = clone $this->sms;
    }
}