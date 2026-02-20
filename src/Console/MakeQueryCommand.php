<?php

namespace LaravelCQRS\Console;

use Illuminate\Console\GeneratorCommand;

class MakeQueryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'cqrs:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CQRS query class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Query';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/query.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $namespace = config('cqrs.query_namespace', 'App\\CQRS\\Queries');
        
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

