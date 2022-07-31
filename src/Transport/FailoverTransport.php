<?php

namespace Trapstats\Sms\Transport;

use Exception;
use RuntimeException;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\Sms;
use Trapstats\Sms\SentMessage;

class FailoverTransport implements TransportContract
{
    /**
     * Create a new failover transport instance.
     *
     * @param  \Trapstats\Sms\Contracts\TransportContract[]  $transports
     * @param  int  $retryPeriod
     * @return void
     */
    public function __construct(
        protected array $transports,
        protected int $retryPeriod = 60
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(Sms $message): ?SentMessage
    {
        foreach ($this->transports as $transport) {
            try {
                if ($sentMessage = $transport->send($message)) {
                    return $sentMessage;
                }
            } catch (Exception) {
                //... Ignore
            }
        }

        throw new RuntimeException("All transports failed");
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'failover';
    }
}