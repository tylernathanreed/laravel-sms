<?php

namespace Reedware\LaravelSMS\Transport;

use Reedware\LaravelSMS\Contracts\Message as MessageContract;
use Reedware\LaravelSMS\Contracts\Transport as TransportContract;

abstract class Transport implements TransportContract
{
    /**
     * Returns the number of recipients.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     *
     * @return integer
     */
    protected function getRecipientCount(MessageContract $message)
    {
        return count((array) $message->getTo());
    }
}