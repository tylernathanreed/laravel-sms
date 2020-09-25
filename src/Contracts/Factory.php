<?php

namespace Reedware\LaravelSMS\Contracts;

use Closure;

interface Factory
{
    /**
     * Returns the specified sms provider instance by name.
     *
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelSMS\Provider
     */
    public function provider($name = null);

    /**
     * Registers a custom transport creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback);
}