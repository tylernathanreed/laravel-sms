<?php

namespace Reedware\LaravelSMS\Tests;

use Reedware\LaravelSMS\Textable;

class SMSTextableTest extends TestCase
{
    public function testTextableSetsRecipientsCorrectly()
    {
        $textable = new WelcomeTextableStub;
        $textable->to('9995551234');
        $this->assertEquals([['carrier' => null, 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo('9995551234'));

        $textable = new WelcomeTextableStub;
        $textable->to('9995551234', 'cellular');
        $this->assertEquals([['carrier' => 'cellular', 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo('9995551234', 'cellular'));
        $this->assertTrue($textable->hasTo('9995551234'));

        $textable = new WelcomeTextableStub;
        $textable->to(['9995551234']);
        $this->assertEquals([['carrier' => null, 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo('9995551234'));
        $this->assertFalse($textable->hasTo('9995551234', 'cellular'));

        $textable = new WelcomeTextableStub;
        $textable->to([['carrier' => 'cellular', 'number' => '9995551234']]);
        $this->assertEquals([['carrier' => 'cellular', 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo('9995551234', 'cellular'));
        $this->assertTrue($textable->hasTo('9995551234'));

        $textable = new WelcomeTextableStub;
        $textable->to(new TextableTestUserStub);
        $this->assertEquals([['carrier' => 'cellular', 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo(new TextableTestUserStub));
        $this->assertTrue($textable->hasTo('9995551234'));

        $textable = new WelcomeTextableStub;
        $textable->to(collect([new TextableTestUserStub]));
        $this->assertEquals([['carrier' => 'cellular', 'number' => '9995551234']], $textable->to);
        $this->assertTrue($textable->hasTo(new TextableTestUserStub));
        $this->assertTrue($textable->hasTo('9995551234'));

        $textable = new WelcomeTextableStub;
        $textable->to(collect([new TextableTestUserStub, new TextableTestUserStub]));
        $this->assertEquals([
            ['carrier' => 'cellular', 'number' => '9995551234'],
            ['carrier' => 'cellular', 'number' => '9995551234'],
        ], $textable->to);
        $this->assertTrue($textable->hasTo(new TextableTestUserStub));
        $this->assertTrue($textable->hasTo('9995551234'));
    }

    public function testTextableBuildsViewData()
    {
        $textable = new WelcomeTextableStub;

        $textable->build();

        $expected = [
            'first_name' => 'Tyler',
            'lastName' => 'Reed',
            'framework' => 'Laravel',
        ];

        $this->assertSame($expected, $textable->buildViewData());
    }

    public function testProviderMayBeSet()
    {
        $textable = new WelcomeTextableStub;

        $textable->provider('array');

        $textable = unserialize(serialize($textable));

        $this->assertSame('array', $textable->provider);
    }
}

class WelcomeTextableStub extends Textable
{
    public $framework = 'Laravel';

    protected $version = 'x.y.z';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Tyler')
             ->withLastName('Reed');
    }
}

class TextableTestUserStub
{
    public $carrier = 'cellular';
    public $number = '9995551234';
}