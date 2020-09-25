<?php

namespace Reedware\LaravelSMS\Transport;

use InvalidArgumentException;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Reedware\LaravelSMS\Contracts\Message as MessageContract;

class EmailTransport extends Transport
{
    /**
     * The mailer implementation.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The carrier gateway mapping.
     *
     * @var array
     */
    protected $gateways;

    /**
     * Creates a new log transport instance.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     *
     * @return void
     */
    public function __construct(MailerContract $mailer, $gateways = [])
    {
        $this->mailer = $mailer;
        $this->gateways = array_merge(static::getDefaultGateways(), $gateways);
    }

    /**
     * Sends the given message; returns the number of recipients who were accepted for delivery.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     * @param  string[]                                $failedRecipients
     *
     * @return int
     */
    public function send(MessageContract $message, &$failedRecipients = null)
    {
        $this->mailer->raw($message->getBody(), function($email) use ($message) {
            $this->buildMessage($email, $message);
        });

        return $this->getRecipientCount($message);
    }

    /**
     * Builds the email message object.
     *
     * @param  \Illuminate\Mail\Message                $email
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     *
     * @return string
     */
    protected function buildMessage($email, $message)
    {
        foreach ($message->getTo() as $recipient) {
            $email->to($this->buildEmail($recipient, $message));
        }

        if(! empty($message->getFrom())) {
            $email->from($message->getFrom()[0]['number']);
        }

        return $email;
    }

    /**
     * Builds the email address of a number.
     *
     * @param  array                                   $recipient
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     *
     * @return string
     */
    protected function buildEmail($recipient, $message)
    {
        if (is_null($carrier = ($recipient['carrier'] ?? null))) {
            throw new InvalidArgumentException('A carrier must be specified when using the email driver.');
        }

        if (is_null($gateway = ($this->gateways[$carrier] ?? null))) {
            throw new InvalidArgumentException("Could not find email gateway for Carrier [{$carrier}]");
        }

        return $recipient['number'] . '@' . $gateway;
    }

    /**
     * Returns the default gateways.
     *
     * @return array
     */
    public static function getDefaultGateways()
    {
        return [
            'airfiremobile' => 'sms.airfiremobile.com',
            'alaskacommunicates' => 'msg.acsalaska.com',
            'ameritech' => 'paging.acswireless.com',
            'assurancewireless' => 'vmobl.com',
            'att' => 'txt.att.net',
            'boostmobile' => 'sms.myboostmobile.com',
            'cleartalk' => 'sms.cleartalk.us',
            'cricket' => 'sms.mycricket.com',
            'metropcs' => 'mymetropcs.com',
            'nextech' => 'sms.ntwls.net',
            'projectfi' => 'msg.fi.google.com',
            'rogerswireless' => 'sms.rogers.com',
            'sprint' => 'messaging.sprintpcs.com',
            'tmobile' => 'tmomail.net',
            'unicel' => 'utext.com',
            'uscellular' => 'email.uscc.net',
            'verizonwireless' => 'vtext.com',
            'virginmobile' => 'vmobl.com'
        ];
    }

    /**
     * Returns the mailer instance.
     *
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function mailer()
    {
        return $this->mailer;
    }
}