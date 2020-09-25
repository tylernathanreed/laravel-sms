<?php

namespace Reedware\LaravelSMS;

use Closure;
use InvalidArgumentException;
use Reedware\LaravelSMS\Events\ManagerBooted;
use Reedware\LaravelSMS\Contracts\Factory as FactoryContract;

/**
 * @mixin \Reedware\LaravelSMS\Provider
 */
class SMSManager implements FactoryContract
{
    use CreatesTransports;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new sms manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->boot();
    }

    /**
     * Boots this sms manager instance.
     *
     * @return void
     */
    protected function boot()
    {
        $this->app['events']->dispatch(new ManagerBooted($this));
    }

    /**
     * Returns a mailer driver instance.
     *
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelSMS\Provider
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Alias of {@see $this->driver()}.
     *
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelSMS\Provider
     */
    public function provider($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Returns the texter from the local cache, and resolves if needed.
     *
     * @param  string  $name
     *
     * @return \Reedware\LaravelSMS\Provider
     */
    protected function get($name)
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolves and returns the given mailer.
     *
     * @param  string  $name
     *
     * @return \Reedware\LaravelSMS\Contracts\Provider
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("SMS Driver [{$name}] is not defined.");
        }

        $provider = new Provider(
            $name,
            $this->app['view'],
            $this->createTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $provider->setQueue($this->app['queue']);
        }

        foreach (['from', 'to'] as $type) {
            $this->setGlobalAddress($provider, $config, $type);
        }

        return $provider;
    }

    /**
     * Sets the global address on the provider by type.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Provider  $provider
     * @param  array                                    $config
     * @param  string                                   $type
     *
     * @return void
     */
    protected function setGlobalAddress($provider, array $config, string $type)
    {
        $address = $config[$type] ?? $this->app['config']["sms.{$type}"];

        if(empty($address)) {
            return;
        }

        if(is_string($address)) {
            $address = ['number' => $address];
        }

        $provider->{'always' . ucfirst($type)}($address['number'], $address['carrier'] ?? null);
    }


    /**
     * Returns the mail connection configuration.
     *
     * @param  string  $name
     *
     * @return array
     */
    protected function getConfig(string $name)
    {
        return $this->app['config']["sms.providers.{$name}"];
    }

    /**
     * Returns the default mail driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['sms.default'];
    }

    /**
     * Sets the default mail driver name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name)
    {
        $this->app['config']['sms.default'] = $name;
    }

    /**
     * Disconnects the given mailer and remove from local cache.
     *
     * @param  string|null  $name
     *
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->drivers[$name]);
    }

    /**
     * Registers a custom transport creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically calls the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}