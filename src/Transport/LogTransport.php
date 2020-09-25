<?php

namespace Reedware\LaravelSMS\Transport;

use Psr\Log\LoggerInterface;
use Reedware\LaravelSMS\Contracts\Message as MessageContract;

class LogTransport extends Transport
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Creates a new log transport instance.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     *
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $this->logger->debug($this->getMessageString($message));

        return $this->getRecipientCount($message);
    }

    /**
     * Returns a loggable string out of a message entity.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Message  $message
     *
     * @return string
     */
    protected function getMessageString(MessageContract $message)
    {
        return implode(PHP_EOL, [
            'From: ' . implode(', ', $message->getFrom()),
            'To: ' . implode(', ', $message->getTo()),
            'Body: ' . $message->getBody()
        ]);
    }

    /**
     * Returns the logger instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function logger()
    {
        return $this->logger;
    }
}