<?php

namespace Reedware\LaravelSMS;

use Reedware\LaravelSMS\Contracts\Factory as FactoryContract;
use Reedware\LaravelSMS\Contracts\Textable as TextableContract;

class SendQueuedTextable
{
    /**
     * The textable message instance.
     *
     * @var \Reedware\LaravelSMS\Contracts\Textable
     */
    public $textable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Textable  $textable
     *
     * @return void
     */
    public function __construct(TextableContract $textable)
    {
        $this->textable = $textable;
        $this->tries = property_exists($textable, 'tries') ? $textable->tries : null;
        $this->timeout = property_exists($textable, 'timeout') ? $textable->timeout : null;
    }

    /**
     * Handles the queued job.
     *
     * @param  \Reedware\LaravelSMS\Contracts\Factory  $factory
     *
     * @return void
     */
    public function handle(FactoryContract $factory)
    {
        $this->textable->send($factory);
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->textable);
    }

    /**
     * Call the failed method on the textable instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->textable, 'failed')) {
            $this->textable->failed($e);
        }
    }

    /**
     * Get number of seconds before a released textable will be available.
     *
     * @return mixed
     */
    public function backoff()
    {
        if (! method_exists($this->textable, 'backoff') && ! isset($this->textable->backoff)) {
            return;
        }

        return $this->textable->backoff ?? $this->textable->backoff();
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->textable = clone $this->textable;
    }
}