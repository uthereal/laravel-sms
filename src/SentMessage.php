<?php

namespace Trapstats\Sms;

class SentMessage
{
    /**
     * Construct a new SentMessage instance.
     *
     * @param  \Trapstats\Sms\Sms  $message
     */
    public function __construct(
        protected Sms $message
    ) {
        //...
    }

    /**
     * Get the sms which was sent.
     *
     * @return \Trapstats\Sms\Sms
     */
    public function getOriginalMessage(): Sms
    {
        return $this->message;
    }
}