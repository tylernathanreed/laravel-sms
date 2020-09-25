<?php

namespace Reedware\LaravelSMS\Tests;

use InvalidArgumentException;

class SMSManagerTest extends TestCase
{
    /**
     * @dataProvider emptyTransportConfigDataProvider
     */
    public function testEmptyTransportConfig($transport)
    {
        $this->app['config']->set('sms.providers.custom_smtp', [
            'transport' => $transport,
            'host' => null,
            'port' => null,
            'encryption' => null,
            'username' => null,
            'password' => null,
            'timeout' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported SMS Transport [{$transport}]");
        $this->app['sms.manager']->provider('custom_smtp');
    }

    public function emptyTransportConfigDataProvider()
    {
        return [
            [null], [''], [' '],
        ];
    }
}