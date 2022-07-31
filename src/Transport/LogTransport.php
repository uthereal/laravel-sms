<?php

namespace Trapstats\Sms\Transport;

use Psr\Log\LoggerInterface;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\Sms;
use Trapstats\Sms\SentMessage;

class LogTransport implements TransportContract
{
    /**
     * Create a new log transport instance
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return void
     */
    public function __construct(
        protected LoggerInterface $logger
    ) {
        //...
    }

    /**
     * @inheritDoc
     */
    public function send(Sms $message): SentMessage
    {
        $this->logger->debug($message);

        return new SentMessage($message);
    }

    /**
     * Get the logger for the LogTransport instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'log';
    }
}