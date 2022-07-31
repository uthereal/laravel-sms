<?php

namespace Trapstats\Sms\Contracts;

use Stringable;
use Trapstats\Sms\Sms;
use Trapstats\Sms\SentMessage;

interface TransportContract extends Stringable
{
    /**
     * Send a message over the transport
     *
     * @param  \Trapstats\Sms\Sms  $message
     * @return \Trapstats\Sms\SentMessage|null
     */
    public function send(Sms $message): ?SentMessage;
}