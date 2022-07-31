<?php

namespace Trapstats\Sms\Contracts;

use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\Messenger;

interface Factory
{
    /**
     * Get a messenger instance by name
     *
     * @param  string|null  $name
     * @return \Trapstats\Sms\Messenger
     */
    public function messenger(string $name = null): MessengerContract;
}