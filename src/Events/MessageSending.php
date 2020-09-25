<?php

namespace Reedware\LaravelSMS\Events;

class MessageSending
{
    /**
     * The sms message instance.
     *
     * @var \Reedware\LaravelSMS\Contracts\Message
     */
    public $message;

    /**
     * The message data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     * @param  array                                   $data
     *
     * @return void
     */
    public function __construct($message, $data = [])
    {
        $this->data = $data;
        $this->message = $message;
    }
}
