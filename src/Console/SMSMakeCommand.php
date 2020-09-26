<?php

namespace Reedware\LaravelSMS\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class SMSMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new textable class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'SMS';

    /**
     * Returns the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '../../stubs/sms.stub';
    }

    /**
     * Returns the default namespace for the class.
     *
     * @param  string  $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\SMS';
    }

    /**
     * Returns the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the textable already exists'],
        ];
    }
}
