<?php

namespace Reedware\LaravelSMS\Tests;

use Reedware\LaravelSMS\Message;

class SMSMessageTest extends TestCase
{
    /**
     * @var \Reedware\LaravelSMS\Message
     */
    protected $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new Message;
    }

    public function testFromMethod()
    {
        $this->assertInstanceOf(Message::class, $this->message->from('9995551234', 'cellular'));
        $this->assertEquals([['number' => '9995551234', 'carrier' => 'cellular']], $this->message->getFrom());
    }

    public function testToMethod()
    {
        $this->assertInstanceOf(Message::class, $this->message->to('9995551234', 'cellular'));
        $this->assertEquals([['number' => '9995551234', 'carrier' => 'cellular']], $this->message->getTo());
    }

    public function testBodyMethod()
    {
        $this->assertInstanceOf(Message::class, $this->message->body('message'));
        $this->assertEquals('message', $this->message->getBody());
    }
}