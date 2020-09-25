<?php

namespace Reedware\LaravelSMS\Contracts;

interface Provider
{
    /**
     * Begins the process of texting a textable class instance.
     *
     * @param  mixed  $users
     *
     * @return \Reedware\LaravelSMS\PendingMessage
     */
    public function to($users);

    /**
     * Sends a new message using the specified message.
     *
     * @param  string  $text
     * @param  mixed   $callback
     *
     * @return void
     */
    public function raw($text, $callback);

    /**
     * Sends a new message using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array                                             $data
     * @param  \Closure|string|null                              $callback
     *
     * @return void
     */
    public function send($view, array $data = [], $callback = null);

    /**
     * Returns the array of failed recipients.
     *
     * @return array
     */
    public function failures();
}