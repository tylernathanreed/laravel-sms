<?php

namespace Reedware\LaravelSMS\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Reedware\LaravelSMS\Events\MessageSending;
use Reedware\LaravelSMS\Events\MessageSent;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Reedware\LaravelSMS\Contracts\Transport as TransportContract;
use Reedware\LaravelSMS\Message;
use Reedware\LaravelSMS\Provider;
use stdClass;

class SMSProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testProviderSendSendsMessageWithProperViewContent()
    {
        // Clear the response
        unset($_SERVER['__sms.test']);

        // Create a new mock sms provider
        $provider = $this->getMockBuilder(Provider::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();

        // Mock the sms message
        $message = m::mock(Message::class);
        $provider->expects($this->once())->method('createMessage')->willReturn($message);

        // Mock the view response
        $view = m::mock(stdClass::class);
        $provider->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        // Mock the sms message mutators
        $message->shouldReceive('setBody')->once()->with('rendered.view');
        $message->shouldReceive('setFrom')->never();

        // Mock the transport
        $this->setTransport($provider);
        $provider->getTransport()->shouldReceive('send')->once()->with($message, []);

        // Send the mock message
        $provider->send('foo', ['data'], function ($m) {
            $_SERVER['__sms.test'] = $m;
        });

        // Clear the response
        unset($_SERVER['__sms.test']);
    }

    public function testProviderSendSendsMessageWithProperRawContentUsingRawMethod()
    {
        // Clear the response
        unset($_SERVER['__sms.test']);

        // Create a new mock sms provider
        $provider = $this->getMockBuilder(Provider::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();

        // Mock the sms message
        $message = m::mock(Message::class);
        $provider->expects($this->once())->method('createMessage')->willReturn($message);

        // Mock the view response
        $view = m::mock(stdClass::class);
        $provider->getViewFactory()->shouldReceive('make')->never();
        $view->shouldReceive('render')->never();

        // Mock the sms message mutators
        $message->shouldReceive('setBody')->once()->with('rendered.view');
        $message->shouldReceive('setFrom')->never();

        // Mock the transport
        $this->setTransport($provider);
        $provider->getTransport()->shouldReceive('send')->once()->with($message, []);

        // Send the mock message
        $provider->raw('rendered.view', function ($m) {
            $_SERVER['__sms.test'] = $m;
        });

        // Clear the response
        unset($_SERVER['__sms.test']);
    }

    public function testGlobalFromIsRespectedOnAllMessages()
    {
        // Clear the response
        unset($_SERVER['__sms.test']);

        // Create a new sms provider
        $provider = $this->getProvider();

        // Mock the view response
        $view = m::mock(stdClass::class);
        $provider->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        // Mock the transport
        $this->setTransport($provider);

        // Prepare the provider
        $provider->alwaysFrom('9995551234', 'cellular');

        // Run the test
        $provider->getTransport()->shouldReceive('send')->once()->with(m::type(Message::class), [])->andReturnUsing(function ($message) {
            $this->assertEquals([['number' => '9995551234', 'carrier' => 'cellular']], $message->getFrom());
        });

        $provider->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testFailedRecipientsAreAppendedAndCanBeRetrieved()
    {
        // Clear the response
        unset($_SERVER['__sms.test']);

        // Create a new sms provider
        $provider = $this->getProvider();

        // Prepare the transport
        $provider->getTransport()->shouldReceive('stop');

        // Mock the view response
        $view = m::mock(stdClass::class);
        $provider->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        // Set the failing transport
        $transport = new FailingTransportStub;
        $provider->setTransport($transport);

        // Send a dummy message
        $provider->send('foo', ['data'], function ($m) {
            //
        });

        // Ensure that it failed correctly
        $this->assertEquals(['9995551234'], $provider->failures());
    }

    public function testEventsAreDispatched()
    {
        // Clear the response
        unset($_SERVER['__sms.test']);

        // Mock the events
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('until')->once()->with(m::type(MessageSending::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(MessageSent::class));

        // Create a new sms provider
        $provider = $this->getProvider($events);

        // Mock the view response
        $view = m::mock(stdClass::class);
        $provider->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        // Mock the transport
        $this->setTransport($provider);
        $provider->getTransport()->shouldReceive('send')->once()->with(m::type(Message::class), []);

        // Send a dummy message
        $provider->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testMacroable()
    {
        Provider::macro('foo', function () {
            return 'bar';
        });

        $provider = $this->getProvider();

        $this->assertSame(
            'bar', $provider->foo()
        );
    }

    protected function getProvider($events = null)
    {
        return new Provider('log', m::mock(ViewFactory::class), m::mock(TransportContract::class), $events);
    }

    public function setTransport($provider)
    {
        $transport = m::mock(TransportContract::class);
        $transport->shouldReceive('stop');
        $provider->setTransport($transport);

        return $provider;
    }

    protected function getMocks()
    {
        return ['log', m::mock(ViewFactory::class), m::mock(TransportContract::class)];
    }
}

class FailingTransportStub
{
    public function send($message, &$failed)
    {
        $failed[] = '9995551234';
    }

    public function getTransport()
    {
        $transport = m::mock(Swift_Transport::class);
        $transport->shouldReceive('stop');

        return $transport;
    }

    public function createMessage()
    {
        return new Message;
    }
}