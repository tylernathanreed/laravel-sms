<?php

namespace Reedware\LaravelSMS\Transport;

use Illuminate\Support\Collection;
use Reedware\LaravelSMS\Contracts\Message as MessageContract;

class ArrayTransport extends Transport
{
    /**
     * The collection of messages.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $messages;

    /**
     * Creates a new array transport instance.
     *
     * @return $this
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * Sends the given message; returns the number of recipients who were accepted for delivery.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     * @param  string[]                                $failedRecipients
     *
     * @return int
     */
    public function send(MessageContract $message, &$failedRecipients = null)
    {
        $this->messages[] = $message;

        return $this->getRecipientCount($message);
    }

    /**
     * Returns the collection of messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clears all of the messages from the local collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}
