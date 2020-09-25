<?php

namespace Reedware\LaravelSMS;

use Illuminate\Contracts\Events\Dispatcher;
use Reedware\LaravelSMS\Contracts\Textable as TextableContract;
use Reedware\LaravelSMS\Contracts\Provider as ProviderContract;
use Reedware\LaravelSMS\Contracts\MessageQueue as MessageQueueContract;
use Reedware\LaravelSMS\Contracts\Transport as TransportContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Reedware\LaravelSMS\Events\MessageSending;
use Reedware\LaravelSMS\Events\MessageSent;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Provider implements ProviderContract, MessageQueueContract
{
    use Macroable;

    /**
     * The name that is configured for the provider.
     *
     * @var string
     */
    protected $name;

    /**
     * The view factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * The sms transport instance.
     *
     * @var \Reedware\LaravelSMS\Contracts\Transport
     */
    protected $transport;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from number.
     *
     * @var array
     */
    protected $from;

    /**
     * The global to number.
     *
     * @var array
     */
    protected $to;

    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * Array of failed recipients.
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     * Create a new sms provider instance.
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @param  \Reedware\LaravelSMS\Contracts\Transport  $transport
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(string $name, ViewFactory $views, TransportContract $transport, Dispatcher $events = null)
    {
        $this->name = $name;
        $this->views = $views;
        $this->transport = $transport;
        $this->events = $events;
    }

    /**
     * Sets the global "from" number.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return void
     */
    public function alwaysFrom($number, $carrier = null)
    {
        $this->from = compact('number', 'carrier');
    }

    /**
     * Sets the global "to" number.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return void
     */
    public function alwaysTo($number, $carrier = null)
    {
        $this->to = compact('number', 'carrier');
    }

    /**
     * Begins the process of texting a textable class instance.
     *
     * @param  mixed  $users
     *
     * @return \Reedware\LaravelSMS\PendingMessage
     */
    public function to($users)
    {
        return (new PendingMessage($this))->to($users);
    }

    /**
     * Sends a new message using the specified message.
     *
     * @param  string  $text
     * @param  mixed   $callback
     *
     * @return void
     */
    public function raw($text, $callback)
    {
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Render the given message as a view.
     *
     * @param  string|array  $view
     * @param  array         $data
     *
     * @return string
     */
    public function render($view, array $data = [])
    {
        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $raw] = $this->parseView($view);

        $data['message'] = $this->createMessage();

        return $this->renderView($view ?: $raw, $data);
    }

    /**
     * Send a new message using a view.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable|string|array  $view
     * @param  array                                                 $data
     * @param  \Closure|string|null                                  $callback
     *
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof TextableContract) {
            return $this->sendTextable($view);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $raw] = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        $callback($message);

        $this->addContent($message, $view, $raw, $data);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if (isset($this->to)) {
            $this->setGlobalTo($message);
        }

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        if (! $this->shouldSendMessage($message, $data)) {
            return;
        }

        $this->sendMessage($message);

        $this->dispatchSentEvent($message, $data);
    }

    /**
     * Sends the given textable.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return mixed
     */
    protected function sendTextable(TextableContract $textable)
    {
        return $textable instanceof ShouldQueue
                        ? $textable->provider($this->name)->queue($this->queue)
                        : $textable->provider($this->name)->send($this);
    }

    /**
     * Parses the given view name or array.
     *
     * @param  string|array  $view
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1]];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     * @param  string                        $view
     * @param  string                        $raw
     * @param  array                         $data
     *
     * @return void
     */
    protected function addContent($message, $view, $raw, $data)
    {
        $body = !empty($view)
            ? $this->renderView($view, $data)
            : $raw;

        $message->setBody($body);
    }

    /**
     * Renders the given view.
     *
     * @param  string  $view
     * @param  array   $data
     *
     * @return string
     */
    protected function renderView($view, $data)
    {
        return $this->views->make($view, $data)->render();
    }

    /**
     * Sets the global "to" address on the given message.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     *
     * @return void
     */
    protected function setGlobalTo($message)
    {
        $message->setTo($this->to);
    }

    /**
     * Queues a new e-mail message for sending.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $view
     * @param  string|null                              $queue
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof TextableContract) {
            throw new InvalidArgumentException('Only textables may be queued.');
        }

        if (is_string($queue)) {
            $view->onQueue($queue);
        }

        return $view->provider($this->name)->queue($this->queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * @param  string  $queue
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $view
     * @return mixed
     */
    public function onQueue($queue, $view)
    {
        return $this->queue($view, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int     $delay
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $view
     * @param  string|null                              $queue
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function later($delay, $view, $queue = null)
    {
        if (! $view instanceof TextableContract) {
            throw new InvalidArgumentException('Only textables may be queued.');
        }

        return $view->provider($this->name)->later(
            $delay, is_null($queue) ? $this->queue : $queue
        );
    }

    /**
     * Queues a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $view
     * @return mixed
     */
    public function laterOn($queue, $delay, $view)
    {
        return $this->later($delay, $view, $queue);
    }

    /**
     * Creates and returns a new message instance.
     *
     * @return \Reedware\LaravelSMS\Message
     */
    protected function createMessage()
    {
        $message = new Message;

        if (! empty($this->from)) {
            $message->setFrom($this->from['number'], $this->from['carrier']);
        }

        return $message;
    }

    /**
     * Sends the specified message instance.
     *
     * @param  Reedware\LaravelSMS\Message  $message
     *
     * @return integer|null
     */
    protected function sendMessage($message)
    {
        $this->failedRecipients = [];

        return $this->transport->send($message, $this->failedRecipients);
    }

    /**
     * Returns whether or not specified the message can be sent.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     * @param  array                         $data
     *
     * @return boolean
     */
    protected function shouldSendMessage($message, $data = [])
    {
        if (! $this->events) {
            return true;
        }

        return $this->events->until(
            new MessageSending($message, $data)
        ) !== false;
    }

    /**
     * Dispatches the message sent event.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     * @param  array                         $data
     *
     * @return void
     */
    protected function dispatchSentEvent($message, $data = [])
    {
        if (!$this->events) {
            return;
        }

        $this->events->dispatch(
            new MessageSent($message, $data)
        );
    }

    /**
     * Returns the array of failed recipients.
     *
     * @return array
     */
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Returns the sms transport instance.
     *
     * @return \Reedware\LaravelSMS\Contracts\Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Returns the view factory instance.
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function getViewFactory()
    {
        return $this->views;
    }

    /**
     * Sets the sms transport instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Transport  $transport
     *
     * @return void
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Sets the queue manager instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     *
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}