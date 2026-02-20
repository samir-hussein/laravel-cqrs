<?php

namespace LaravelCQRS\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeCommandCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'cqrs:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CQRS command class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Command';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/command.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $namespace = config('cqrs.command_namespace', 'App\\CQRS\\Commands');
        
        // If config returns a full namespace, use it; otherwise append to root
        if (strpos($namespace, 'App\\') === 0) {
            return $namespace;
        }
        
        return $rootNamespace . '\\' . str_replace('/', '\\', $namespace);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass($stub, $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        $stub = str_replace('{{ class }}', $class, $stub);
        $stub = str_replace('{{ namespace }}', $this->getNamespace($name), $stub);

        return $stub;
    }
}

