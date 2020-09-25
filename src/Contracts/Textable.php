<?php

namespace Reedware\LaravelSMS\Contracts;

use Illuminate\Contracts\Queue\Factory as Queue;

interface Textable
{
    /**
     * Sends the message using the given sms provider.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Factory|\Reedware\LaravelSMS\Contracts\Provider  $provider
     *
     * @return void
     */
    public function send($provider);

    /**
     * Queues the given message.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     *
     * @return mixed
     */
    public function queue(Queue $queue);

    /**
     * Delivers the queued message after the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory   $queue
     *
     * @return mixed
     */
    public function later($delay, Queue $queue);

    /**
     * Sets the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null          $name
     *
     * @return $this
     */
    public function to($address, $name = null);

    /**
     * Sets the locale of the message.
     *
     * @param  string  $locale
     *
     * @return $this
     */
    public function locale($locale);

    /**
     * Sets the name of the sms provider that should be used to send the message.
     *
     * @param  string  $provider
     *
     * @return $this
     */
    public function provider($provider);
}