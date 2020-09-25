<?php

namespace Reedware\LaravelSMS\Events;

use Reedware\LaravelSMS\Contracts\Factory as Manager;

class ManagerBooted
{
    /**
     * The sms message instance.
     *
     * @var \Reedware\LaravelSMS\Contracts\Factory
     */
    public $manager;

    /**
     * Create a new event instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     *
     * @return void
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
}
