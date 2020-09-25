<?php

namespace Reedware\LaravelSMS;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Reedware\LaravelSMS\Contracts\Provider as ProviderContract;
use Reedware\LaravelSMS\Contracts\Textable as TextableContract;

class PendingMessage
{
    /**
     * The sms provider instance.
     *
     * @var \Reedware\LaravelSMS\Contracts\Provider
     */
    protected $provider;

    /**
     * The locale of the message.
     *
     * @var string
     */
    protected $locale;

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * Create a new pending sms message instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Provider  $provider
     *
     * @return void
     */
    public function __construct(ProviderContract $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Sets the locale of the message.
     *
     * @param  string  $locale
     *
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Sets the recipients of the message.
     *
     * @param  mixed  $users
     *
     * @return $this
     */
    public function to($users)
    {
        $this->to = $users;

        if (! $this->locale && $users instanceof HasLocalePreference) {
            $this->locale($users->preferredLocale());
        }

        return $this;
    }

    /**
     * Send a new textable message instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return mixed
     */
    public function send(TextableContract $textable)
    {
        return $this->provider->send($this->fill($textable));
    }

    /**
     * Push the given textable onto the queue.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return mixed
     */
    public function queue(TextableContract $textable)
    {
        return $this->provider->queue($this->fill($textable));
    }

    /**
     * Deliver the queued message after the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int     $delay
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return mixed
     */
    public function later($delay, TextableContract $textable)
    {
        return $this->provider->later($delay, $this->fill($textable));
    }

    /**
     * Populate the textable with the addresses.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return \Illuminate\Mail\Mailable
     */
    protected function fill(TextableContract $textable)
    {
        return tap($textable->to($this->to), function (TextableContract $textable) {
            if ($this->locale) {
                $textable->locale($this->locale);
            }
        });
    }
}