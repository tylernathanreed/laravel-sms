<?php

namespace Reedware\LaravelSMS;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Log\LogManager;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Reedware\LaravelSMS\Transport\ArrayTransport;
use Reedware\LaravelSMS\Transport\EmailTransport;
use Reedware\LaravelSMS\Transport\LogTransport;

trait CreatesTransports
{
    /**
     * Create a new transport instance.
     *
     * @param  array  $config
     * @return \Swift_Transport
     */
    public function createTransport(array $config)
    {
        $transport = $config['transport'];

        if (isset($this->customCreators[$transport])) {
            return call_user_func($this->customCreators[$transport], $config);
        }

        if (trim($transport) === '' || ! method_exists($this, $method = 'create' . ucfirst($transport) . 'Transport')) {
            throw new InvalidArgumentException("Unsupported SMS Transport [{$transport}].");
        }

        return $this->{$method}($config);
    }

    /**
     * Creates an instance of the array sms transport driver.
     *
     * @return \Reedware\LaravelSMS\Transport\ArrayTransport
     */
    protected function createArrayTransport()
    {
        return new ArrayTransport;
    }

    /**
     * Creates an instance of the email sms transport driver.
     *
     * @param  array  $config
     *
     * @return \Reedware\LaravelSMS\Transport\EmailTransport
     */
    protected function createEmailTransport(array $config)
    {
        $mailer = $this->app->make(MailerContract::class);

        return new EmailTransport($mailer, $config['gateways'] ?? []);
    }

    /**
     * Creates an instance of the log sms transport driver.
     *
     * @param  array  $config
     *
     * @return \Reedware\LaravelSMS\Transport\LogTransport
     */
    protected function createLogTransport(array $config)
    {
        $logger = $this->app->make(LoggerInterface::class);

        if ($logger instanceof LogManager) {
            $logger = $logger->channel($config['channel']);
        }

        return new LogTransport($logger);
    }
}