<?php

namespace Reedware\LaravelSMS;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Reedware\LaravelSMS\Console\SMSMakeCommand;
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
        $this->registerServices();
        $this->registerConfiguration();
    }

    /**
     * Registers the package services.
     *
     * @return void
     */
    protected function registerServices()
    {
        $this->app->singleton('sms.manager', function ($app) {
            return new SMSManager($app);
        });

        $this->app->bind('sms', function ($app) {
            return $app->make('sms.manager')->driver();
        });

        $allAliases = [
            'sms' => [Provider::class, ProviderContract::class, MessageQueueContract::class],
            'sms.manager' => [SMSManager::class, FactoryContract::class]
        ];

        foreach($allAliases as $key => $aliases) {
            foreach($aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Registers the package configuration.
     *
     * @return void
     */
    protected function registerConfiguration()
    {
        $this->mergeConfigFrom(
            $this->configPath(), 'sms'
        );
    }

    /**
     * Bootstraps the package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfiguration();
        $this->registerCommands();
    }

    /**
     * Publishes the package configuration.
     *
     * @return void
     */
    protected function publishConfiguration()
    {
        $this->publishes([
            $this->configPath() => $this->app->configPath('sms.php'),
        ]);
    }

    /**
     * Registers the package commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->app->singleton('command.sms.make', function($app) {
            return new SMSMakeCommand($app['files']);
        });

        $this->commands(['command.sms.make']);
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