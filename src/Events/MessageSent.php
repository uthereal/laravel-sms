<?php

namespace Trapstats\Sms\Events;

use Exception;
use Trapstats\Sms\SentMessage;

/**
 * @property \Trapstats\Sms\Sms $message
 */
class MessageSent
{
    /**
     * Construct a new event instance.
     *
     * @param  \Trapstats\Sms\SentMessage  $sent
     */
    public function __construct(
        public SentMessage $sent
    ) {
        //...
    }

    /**
     * Dynamically get the original message.
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $key): mixed
    {
        if ($key === 'message') {
            return $this->sent->getOriginalMessage();
        }

        throw new Exception('Unable to access undefined property on '.__CLASS__.': '.$key);
    }
}