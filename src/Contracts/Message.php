<?php

namespace Reedware\LaravelSMS\Contracts;

interface Message
{
    /**
     * Sets the sender of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function setFrom(string $number, string $carrier = null);


    /**
     * Returns the sender of the message.
     *
     * @return array
     */
    public function getFrom();

    /**
     * Sets the recipients of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function setTo(string $number, string $carrier = null);

    /**
     * Returns the recipients of the message.
     *
     * @return array
     */
    public function getTo();

    /**
     * Sets the body of the message.
     *
     * @param  string  $body
     *
     * @return $this
     */
    public function setBody(string $body);

    /**
     * Returns the body of the message.
     *
     * @return $this
     */
    public function getBody();
}