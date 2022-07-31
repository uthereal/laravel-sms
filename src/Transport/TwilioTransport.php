<?php

namespace Trapstats\Sms\Transport;

use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\SentMessage;
use Trapstats\Sms\Sms;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioTransport implements TransportContract
{
    /**
     * Create a new Twilio transport instance.
     *
     * @param  \Twilio\Rest\Client  $client
     * @param  array  $options
     */
    public function __construct(
        protected Client $client,
        protected array $options
    ) {
        //...
    }

    /**
     * @inheritDoc
     */
    public function send(Sms $message): ?SentMessage
    {
        $from = $message->getFrom() ?: $this->options['from'];

        foreach ($message->getTo() as $to) {
            try {
                $this->client->messages->create($to, [
                    ...$this->options,
                    'from' => $from,
                    'body' => $message->getContent(),
                ]);
            } catch (TwilioException) {
                return null;
            }
        }

        return new SentMessage($message);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'twilio';
    }
}