<?php

namespace Reedware\LaravelSMS;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Reedware\LaravelSMS\Contracts\Factory as FactoryContract;
use Reedware\LaravelSMS\Contracts\MessageQueue as MessageQueueContract;
use Reedware\LaravelSMS\Contracts\Provider as ProviderContract;

class SMSServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTexter();

        $this->mergeConfigFrom(
            $this->configPath(), 'sms'
        );
    }

    /**
     * Register the sms instance.
     *
     * @return void
     */
    protected function registerTexter()
    {
        $this->app->singleton('sms.manager', function ($app) {
            return new SMSManager($app);
        });

        $this->app->bind('sms', function ($app) {
            return $app->make('sms.manager')->driver();
        });

        $this->app->alias('sms.manager', SMSManager::class);
        $this->app->alias('sms.manager', FactoryContract::class);
        $this->app->alias('sms', Provider::class);
        $this->app->alias('sms', ProviderContract::class);
        $this->app->alias('sms', MessageQueueContract::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath() => $this->app->configPath('sms.php'),
        ]);
    }

    /**
     * Returns the path to the local configuration.
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '../../config/sms.php';
    }

    /**
     * Returns the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'sms.manager',
            'sms'
        ];
    }
}