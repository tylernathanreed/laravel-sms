<?php

namespace Reedware\LaravelSMS;

use Reedware\LaravelSMS\Contracts\Message as MessageContract;

class Message implements MessageContract
{
    /**
     * The person the message is from.
     *
     * @var array
     */
    public $from = [];

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    public $to = [];

    /**
     * The text to use for the message.
     *
     * @var string
     */
    public $body;

    /**
     * Sets the sender of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function setFrom(string $number, string $carrier = null)
    {
        $this->from[] = compact('number', 'carrier');

        return $this;
    }

    /**
     * Alias of {@see $this->setFrom()}.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function from(string $number, string $carrier = null)
    {
        return $this->setFrom($number, $carrier);
    }

    /**
     * Returns the sender of the message.
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Sets the recipients of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function setTo(string $number, string $carrier = null)
    {
        $this->to[] = compact('number', 'carrier');

        return $this;
    }

    /**
     * Alias of {@see $this->setTo()}.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function to(string $number, string $carrier = null)
    {
        return $this->setTo($number, $carrier);
    }

    /**
     * Returns the recipients of the message.
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Sets the body of the message.
     *
     * @param  string  $body
     *
     * @return $this
     */
    public function setBody(string $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Alias of {@see $this->setBody()}.
     *
     * @param  string  $body
     *
     * @return $this
     */
    public function body(string $body)
    {
        return $this->setBody($body);
    }

    /**
     * Returns the body of the message.
     *
     * @return $this
     */
    public function getBody()
    {
        return $this->body;
    }
}
