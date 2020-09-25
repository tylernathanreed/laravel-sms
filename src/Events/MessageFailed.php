<?php

namespace Reedware\LaravelSMS\Events;

use Throwable;

class MessageFailed
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
     * The failing exception.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     * @param  array                                   $data
     * @param  \Throwable                              $exception
     *
     * @return void
     */
    public function __construct($message, $data, Throwable $exception)
    {
        $this->data = $data;
        $this->message = $message;
        $this->exception = $exception;
    }
}
