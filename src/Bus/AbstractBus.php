<?php

namespace LaravelCQRS\Bus;

use Illuminate\Contracts\Container\Container;
use LaravelCQRS\Command;
use LaravelCQRS\Exceptions\HandlerNotFoundException;
use LaravelCQRS\Exceptions\InvalidHandlerException;
use LaravelCQRS\Pipeline;
use LaravelCQRS\Query;

abstract class AbstractBus
{
    /**
     * AbstractBus constructor.
     *
     * @param Container $container
     */
    public function __construct(
        protected Container $container
    ) {}

    /**
     * Resolve the handler for a command or query.
     *
     * @param object $commandOrQuery
     * @param string $handlerInterface
     * @return object
     */
    protected function resolveHandler(object $commandOrQuery, string $handlerInterface): object
    {
        $class = get_class($commandOrQuery);
        $handlerClass = $this->getHandlerClass($class);

        if (!class_exists($handlerClass)) {
            throw HandlerNotFoundException::for($handlerClass, $class);
        }

        /** @var object $handler */
        $handler = $this->container->make($handlerClass);

        if (!$handler instanceof $handlerInterface) {
            throw InvalidHandlerException::for($handlerClass, $handlerInterface);
        }

        return $handler;
    }

    /**
     * Get handler class name from command/query class name.
     *
     * @param string $class
     * @return string
     */
    protected function getHandlerClass(string $class): string
    {
        // Always check handler mappings first (works as override for specific cases)
        $mappings = config('cqrs.handler_mappings', []);
        if (isset($mappings[$class])) {
            return $mappings[$class];
        }

        // If no mapping found, check if auto-resolution is enabled
        if (!config('cqrs.auto_resolve_handlers', true)) {
            // Auto-resolution disabled and no mapping found - throw exception
            throw HandlerNotFoundException::noMapping($class);
        }

        // Auto-resolution enabled - use configuration namespaces if available
        $handlerNamespace = config('cqrs.handler_namespace');
        $commandNamespace = config('cqrs.command_namespace');
        $queryNamespace = config('cqrs.query_namespace');

        // Determine if it's a command or query based on namespace
        if ($commandNamespace && strpos($class, $commandNamespace) === 0) {
            return $this->resolveCommandHandler($class, $handlerNamespace, $commandNamespace);
        }

        if ($queryNamespace && strpos($class, $queryNamespace) === 0) {
            return $this->resolveQueryHandler($class, $handlerNamespace, $queryNamespace);
        }

        // Fallback to default convention-based resolution
        return $this->resolveByConvention($class);
    }

    /**
     * Resolve command handler using configuration.
     *
     * @param string $commandClass
     * @param string|null $handlerNamespace
     * @param string|null $commandNamespace
     * @return string
     */
    protected function resolveCommandHandler(string $commandClass, ?string $handlerNamespace, ?string $commandNamespace): string
    {
        if ($handlerNamespace && $commandNamespace) {
            // Replace command namespace with handler namespace
            $relativePath = str_replace($commandNamespace, '', $commandClass);
            // Remove leading backslash if present
            $relativePath = ltrim($relativePath, '\\');
            // Replace "Command" with "CommandHandler"
            $handlerName = str_replace('Command', 'CommandHandler', $relativePath);
            
            return $handlerNamespace . '\\' . $handlerName;
        }

        return $this->resolveByConvention($commandClass);
    }

    /**
     * Resolve query handler using configuration.
     *
     * @param string $queryClass
     * @param string|null $handlerNamespace
     * @param string|null $queryNamespace
     * @return string
     */
    protected function resolveQueryHandler(string $queryClass, ?string $handlerNamespace, ?string $queryNamespace): string
    {
        if ($handlerNamespace && $queryNamespace) {
            // Replace query namespace with handler namespace
            $relativePath = str_replace($queryNamespace, '', $queryClass);
            // Remove leading backslash if present
            $relativePath = ltrim($relativePath, '\\');
            // Replace "Query" with "QueryHandler"
            $handlerName = str_replace('Query', 'QueryHandler', $relativePath);
            
            return $handlerNamespace . '\\' . $handlerName;
        }

        return $this->resolveByConvention($queryClass);
    }

    /**
     * Resolve handler by naming convention (fallback).
     *
     * @param string $class
     * @return string
     */
    protected function resolveByConvention(string $class): string
    {
        // Replace "Commands" with "Handlers" and append "Handler"
        $handlerClass = str_replace('Commands', 'Handlers', $class);
        $handlerClass = str_replace('Command', 'CommandHandler', $handlerClass);
        
        // Replace "Queries" with "Handlers" and append "Handler"
        $handlerClass = str_replace('Queries', 'Handlers', $handlerClass);
        $handlerClass = str_replace('Query', 'QueryHandler', $handlerClass);

        return $handlerClass;
    }

    /**
     * Get middleware for a command or query.
     * 
     * @param Command|Query $commandOrQuery
     * @return array
     */
    protected function getMiddleware(Command|Query $commandOrQuery): array
    {
        $class = get_class($commandOrQuery);
        
        // Get global middleware
        $globalMiddleware = config('cqrs.middleware.global', []);
        
        // Get command/query specific middleware
        $specificMiddleware = config("cqrs.middleware.{$class}", []);
        
        // Merge: global first, then specific
        return array_merge($globalMiddleware, $specificMiddleware);
    }

    /**
     * Execute handler through middleware pipeline.
     * 
     * @param Command|Query $commandOrQuery
     * @param object $handler
     * @param string $handlerMethod
     * @return mixed
     */
    protected function executeThroughPipeline(Command|Query $commandOrQuery, object $handler, string $handlerMethod): mixed
    {
        $middleware = $this->getMiddleware($commandOrQuery);
        
        // If no middleware, execute directly
        if (empty($middleware)) {
            return $handler->{$handlerMethod}($commandOrQuery);
        }
        
        // Create pipeline
        $pipeline = new Pipeline(
            $middleware,
            function ($commandOrQuery) use ($handler, $handlerMethod) {
                return $handler->{$handlerMethod}($commandOrQuery);
            }
        );
        
        return $pipeline->execute($commandOrQuery);
    }
}

