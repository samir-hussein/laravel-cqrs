<?php

namespace LaravelCQRS\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeHandlerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'cqrs:handler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CQRS handler class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Handler';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        $handlerType = $this->option('type') ?? 'command';
        
        if ($handlerType === 'query') {
            return __DIR__ . '/stubs/query-handler.stub';
        }
        
        return __DIR__ . '/stubs/command-handler.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $namespace = config('cqrs.handler_namespace', 'App\\CQRS\\Handlers');
        
        // If config returns a full namespace, use it; otherwise append to root
        if (strpos($namespace, 'App\\') === 0) {
            return $namespace;
        }
        
        return $rootNamespace . '\\' . str_replace('/', '\\', $namespace);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['type', 't', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Handler type (command or query)', 'command'],
        ];
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
        $handlerType = $this->option('type') ?? 'command';
        
        // Determine command/query class name from handler name
        $commandOrQueryName = $this->getCommandOrQueryName($class, $handlerType);
        
        // Get full namespace path for command/query (including subdirectories)
        $commandOrQueryFullNamespace = $this->getCommandOrQueryFullNamespace($name, $handlerType);
        
        $stub = str_replace('{{ class }}', $class, $stub);
        $stub = str_replace('{{ namespace }}', $this->getNamespace($name), $stub);
        $stub = str_replace('{{ commandOrQuery }}', $commandOrQueryName, $stub);
        $stub = str_replace('{{ commandOrQueryNamespace }}', $commandOrQueryFullNamespace, $stub);
        $stub = str_replace('{{ handlerType }}', ucfirst($handlerType), $stub);
        $stub = str_replace('{{ handlerInterface }}', $handlerType === 'query' ? 'QueryHandlerInterface' : 'CommandHandlerInterface', $stub);
        $stub = str_replace('{{ baseClass }}', $handlerType === 'query' ? 'Query' : 'Command', $stub);

        return $stub;
    }

    /**
     * Get command or query class name from handler name.
     *
     * @param string $handlerName
     * @param string $type
     * @return string
     */
    protected function getCommandOrQueryName(string $handlerName, string $type): string
    {
        // Remove "Handler" suffix
        $name = Str::replaceLast('Handler', '', $handlerName);
        
        // Remove "Command" or "Query" suffix if present
        $name = Str::replaceLast(ucfirst($type), '', $name);
        
        // Add back the appropriate suffix
        return $name . ucfirst($type);
    }

    /**
     * Get the full namespace path for command/query.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getCommandOrQueryFullNamespace(string $name, string $type): string
    {
        $namespace = $this->getCommandOrQueryNamespace($type);
        $commandOrQueryName = $this->getCommandOrQueryName(
            str_replace($this->getNamespace($name) . '\\', '', $name),
            $type
        );
        
        // Extract the subdirectory path from handler namespace
        $handlerNamespace = $this->getNamespace($name);
        $handlerBaseNamespace = config('cqrs.handler_namespace', 'App\\CQRS\\Handlers');
        
        // Get relative path from handler namespace
        $relativePath = str_replace($handlerBaseNamespace, '', $handlerNamespace);
        $relativePath = trim($relativePath, '\\');
        
        // Build command/query namespace with same path
        if ($relativePath) {
            return $namespace . '\\' . $relativePath . '\\' . $commandOrQueryName;
        }
        
        return $namespace . '\\' . $commandOrQueryName;
    }

    /**
     * Get command or query namespace.
     *
     * @param string $type
     * @return string
     */
    protected function getCommandOrQueryNamespace(string $type): string
    {
        if ($type === 'query') {
            return config('cqrs.query_namespace', 'App\\CQRS\\Queries');
        }
        
        return config('cqrs.command_namespace', 'App\\CQRS\\Commands');
    }
}

