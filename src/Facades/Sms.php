<?php

namespace Trapstats\Sms\Facades;

use Illuminate\Support\Facades\Facade;
use Trapstats\Sms\Testing\Fakes\SmsFake;

class Sms extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Trapstats\Sms\Testing\Fakes\SmsFake
     */
    public static function fake(): SmsFake
    {
        static::swap($fake = new SmsFake());

        return $fake;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'sms.manager';
    }
}