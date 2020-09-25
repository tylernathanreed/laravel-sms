<?php

namespace Reedware\LaravelSMS;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Localizable;
use Reedware\LaravelSMS\Contracts\Factory as FactoryContract;
use Reedware\LaravelSMS\Contracts\Textable as TextableContract;
use ReflectionClass;
use ReflectionProperty;

class Textable implements TextableContract, Renderable
{
    use ForwardsCalls, Localizable;

    /**
     * The locale of the message.
     *
     * @var string
     */
    public $locale;

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
    protected $text;

    /**
     * The view to use for the message.
     *
     * @var string
     */
    public $view;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The callbacks for the message.
     *
     * @var array
     */
    public $callbacks = [];

    /**
     * The name of the provider that should send the message.
     *
     * @var string
     */
    public $provider;

    /**
     * The callback that should be invoked while building the view data.
     *
     * @var callable
     */
    public static $viewDataCallback;

    /**
     * Sends the message using the given sms provider.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Factory|\Reedware\LaravelSMS\Contracts\Provider  $provider
     *
     * @return void
     */
    public function send($provider)
    {
        return $this->withLocale($this->locale, function () use ($provider) {
            Container::getInstance()->call([$this, 'build']);

            $provider = $provider instanceof FactoryContract
                            ? $provider->provider($this->provider)
                            : $provider;

            return $provider->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                     ->buildRecipients($message)
                     ->runCallbacks($message);
            });
        });
    }

    /**
     * Queues the message for sending.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     *
     * @return mixed
     */
    public function queue(Queue $queue)
    {
        if (isset($this->delay)) {
            return $this->later($this->delay, $queue);
        }

        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->pushOn(
            $queueName ?: null, $this->newQueuedJob()
        );
    }

    /**
     * Delivers the queued message after the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     *
     * @return mixed
     */
    public function later($delay, Queue $queue)
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->laterOn(
            $queueName ?: null, $delay, $this->newQueuedJob()
        );
    }

    /**
     * Creates and returns a new queued textable job instance.
     *
     * @return mixed
     */
    protected function newQueuedJob()
    {
        return new SendQueuedTextable($this);
    }

    /**
     * Render the mailable into a view.
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        return $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            return Container::getInstance()->make('sms')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }

    /**
     * Build the view for the message.
     *
     * @return array|string
     *
     * @throws \ReflectionException
     */
    protected function buildView()
    {
        if (isset($this->text)) {
            return $this->text;
        }

        return $this->view;
    }

    /**
     * Build the view data for the message.
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function buildViewData()
    {
        $data = $this->viewData;

        if (static::$viewDataCallback) {
            $data = array_merge($data, call_user_func(static::$viewDataCallback, $this));
        }

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Adds the sender to the message.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     *
     * @return $this
     */
    protected function buildFrom($message)
    {
        if (! empty($this->from)) {
            $message->from($this->from);
        }

        return $this;
    }

    /**
     * Adds all of the recipients to the message.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     *
     * @return $this
     */
    protected function buildRecipients($message)
    {
        foreach ($this->to as $recipient) {
            $message->to($recipient);
        }

        return $this;
    }

    /**
     * Runs the callbacks for the message.
     *
     * @param  \Reedware\LaravelSMS\Message  $message
     *
     * @return $this
     */
    protected function runCallbacks($message)
    {
        foreach ($this->callbacks as $callback) {
            $callback($message);
        }

        return $this;
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
     * Sets the sender of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function from($number, $carrier = null)
    {
        return $this->setAddress($number, $carrier, 'from');
    }

    /**
     * Returns whether or not if the given recipient is set on the textable.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return boolean
     */
    public function hasFrom($number, $carrier = null)
    {
        return $this->hasRecipient($number, $carrier, 'from');
    }

    /**
     * Sets the recipients of the message.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return $this
     */
    public function to($number, $carrier = null)
    {
        return $this->setAddress($number, $carrier, 'to');
    }

    /**
     * Returns whether or not the given recipient is set on the textable.
     *
     * @param  string       $number
     * @param  string|null  $carrier
     *
     * @return bool
     */
    public function hasTo($number, $carrier = null)
    {
        return $this->hasRecipient($number, $carrier, 'to');
    }

    /**
     * Sets the recipients of the message.
     *
     * All recipients are stored internally as [['number' => ?, 'carrier' => ?]]
     *
     * @param  object|array|string  $number
     * @param  string|null          $carrier
     * @param  string               $property
     *
     * @return $this
     */
    protected function setAddress($number, $carrier = null, $property = 'to')
    {
        foreach ($this->addressesToArray($number, $carrier) as $recipient) {
            $recipient = $this->normalizeRecipient($recipient);

            $this->{$property}[] = [
                'number' => $recipient->number,
                'carrier' => $recipient->carrier ?? null,
            ];
        }

        return $this;
    }

    /**
     * Converts the given recipient arguments to an array.
     *
     * @param  object|array|string  $number
     * @param  string|null          $carrier
     *
     * @return array
     */
    protected function addressesToArray($number, $carrier)
    {
        if (! is_array($number) && ! $number instanceof Collection) {
            $number = is_string($carrier) ? [compact('number', 'carrier')] : [$number];
        }

        return $number;
    }

    /**
     * Converts the given recipient into an object.
     *
     * @param  mixed  $recipient
     *
     * @return object
     */
    protected function normalizeRecipient($recipient)
    {
        if (is_array($recipient)) {
            if (array_values($recipient) === $recipient) {
                return (object) array_map(function ($number) {
                    return compact('number');
                }, $recipient);
            }

            return (object) $recipient;
        } elseif (is_string($recipient)) {
            return (object) ['number' => $recipient];
        }

        return $recipient;
    }

    /**
     * Returns whether or not the given recipient is set on the mailable.
     *
     * @param  object|array|string  $number
     * @param  string|null          $carrier
     * @param  string               $property
     *
     * @return boolean
     */
    protected function hasRecipient($number, $carrier = null, $property = 'to')
    {
        $expected = $this->normalizeRecipient(
            $this->addressesToArray($number, $carrier)[0]
        );

        $expected = [
            'number' => $expected->number,
            'carrier' => $expected->carrier ?? null,
        ];

        return collect($this->{$property})->contains(function ($actual) use ($expected) {
            if (! isset($expected['carrier'])) {
                return $actual['number'] == $expected['number'];
            }

            return $actual == $expected;
        });
    }

    /**
     * Sets the view and view data for the message.
     *
     * @param  string  $view
     * @param  array   $data
     *
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Sets the rendered text content for the message.
     *
     * @param  string  $text
     *
     * @return $this
     */
    public function text($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the view data for the message.
     *
     * @param  string|array  $key
     * @param  mixed         $value
     *
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    /**
     * Sets the name of the provider that should send the message.
     *
     * @param  string  $provider
     *
     * @return $this
     */
    public function provider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Registers a callback to be called with the message instance.
     *
     * @param  callable  $callback
     *
     * @return $this
     */
    public function withTransportMessage(callable $callback)
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Registers a callback to be called while building the view data.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    public static function buildViewDataUsing(callable $callback)
    {
        static::$viewDataCallback = $callback;
    }

    /**
     * Apply the callback's message changes if the given "value" is true.
     *
     * @param  mixed     $value
     * @param  callable  $callback
     * @param  mixed     $default
     *
     * @return mixed|$this
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Dynamically binds parameters to the message.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'with')) {
            return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
        }

        static::throwBadMethodCallException($method);
    }
}
