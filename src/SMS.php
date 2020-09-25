<?php

namespace Reedware\LaravelSMS;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Reedware\LaravelSMS\SMSProvider
 */
class SMS extends Facade
{
    /**
     * Returns the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sms.manager';
    }
}
