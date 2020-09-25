<?php

namespace Reedware\LaravelSMS\Contracts;

interface Transport
{
    /**
     * Sends the given message; returns the number of recipients who were accepted for delivery.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     * @param  string[]                                $failedRecipients
     *
     * @return int
     */
    public function send(Message $message, &$failedRecipients = null);
}