<?php

namespace Reedware\LaravelSMS\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Application;
use Reedware\LaravelSMS\Provider;
use Reedware\LaravelSMS\SendQueuedTextable;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use Reedware\LaravelSMS\Contracts\Transport;
use Reedware\LaravelSMS\Textable;

class TextableQueuedTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testQueuedTextableSent()
    {
        // Mock the queue
        $queueFake = new QueueFake(new Application);

        // Mock the sms provider
        $provider = $this->getMockBuilder(Provider::class)
            ->setConstructorArgs($this->getMocks())
            ->setMethods(['createMessage', 'to'])
            ->getMock();

        // Prepare the queue
        $provider->setQueue($queueFake);
        $textable = new TextableQueableStub;
        $queueFake->assertNothingPushed();

        // Perform the test
        $provider->send($textable);

        // Confirm it worked
        $queueFake->assertPushedOn(null, SendQueuedTextable::class);
    }

    protected function getMocks()
    {
        return ['log', m::mock(ViewFactory::class), m::mock(Transport::class)];
    }
}

class TextableQueableStub extends Textable implements ShouldQueue
{
    use Queueable;

    public function build(): self
    {
        $this
            ->subject('lorem ipsum')
            ->html('foo bar baz')
            ->to('foo@example.tld');

        return $this;
    }
}