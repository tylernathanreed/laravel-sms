<?php

namespace Reedware\LaravelSMS\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Reedware\LaravelSMS\Transport\LogTransport;

class SMSLogTransportTest extends TestCase
{
    public function testGetLogTransportWithConfiguredChannel()
    {
        $this->app['config']->set('sms.default', 'log');

        $this->app['config']->set('sms.log.channel', 'sms');

        $this->app['config']->set('logging.channels.sms', [
            'driver' => 'single',
            'path' => 'sms.log',
        ]);

        $transport = $this->app['sms']->getTransport();
        $this->assertInstanceOf(LogTransport::class, $transport);

        $logger = $transport->logger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $handler = $handlers[0]);
    }

    public function testGetLogTransportWithPsrLogger()
    {
        $this->app['config']->set('sms.default', 'log');
        $logger = $this->app->instance('log', new NullLogger());

        $transportLogger = $this->app['sms']->getTransport()->logger();

        $this->assertEquals($logger, $transportLogger);
    }
}