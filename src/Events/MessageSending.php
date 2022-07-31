<?php

namespace Trapstats\Sms\Events;

use Trapstats\Sms\Sms;

class MessageSending
{
    /**
     * @param  \Trapstats\Sms\Sms  $message
     */
    public function __construct(
        public Sms $message
    ) {
    }
}