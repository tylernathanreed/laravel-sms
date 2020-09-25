<?php

namespace Reedware\LaravelSMS\Tests;

use Reedware\LaravelSMS\Textable;

class SMSTextableDataTest extends TestCase
{
    public function testTextableDataIsNotLost()
    {
        $testData = ['first_name' => 'Tyler'];

        $mailable = new TextableStub;
        $mailable->build(function ($m) use ($testData) {
                $m->view('view', $testData);
        });
        $this->assertSame($testData, $mailable->buildViewData());

        $mailable = new TextableStub;
        $mailable->build(function ($m) use ($testData) {
                $m->view('view', $testData)
                    ->text('text-view');
        });
        $this->assertSame($testData, $mailable->buildViewData());
    }
}

class TextableStub extends Textable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($builder)
    {
        $builder($this);
    }
}