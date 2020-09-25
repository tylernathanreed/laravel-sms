<?php

namespace Reedware\LaravelSMS;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * @see \Reedware\LaravelSMS\Provider
 */
class Facade extends BaseFacade
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
