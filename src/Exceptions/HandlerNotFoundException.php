<?php

namespace LaravelCQRS\Exceptions;

use RuntimeException;

class HandlerNotFoundException extends RuntimeException
{
    /**
     * Create a new handler not found exception.
     *
     * @param string $handlerClass
     * @param string $commandOrQueryClass
     * @return static
     */
    public static function for(string $handlerClass, string $commandOrQueryClass): static
    {
        return new static(
            "Handler class {$handlerClass} not found for {$commandOrQueryClass}"
        );
    }

    /**
     * Create a new handler not found exception when auto-resolution is disabled
     * and no handler mapping exists.
     *
     * @param string $commandOrQueryClass
     * @return static
     */
    public static function noMapping(string $commandOrQueryClass): static
    {
        return new static(
            "No handler mapping found for {$commandOrQueryClass}. " .
            "Either enable auto_resolve_handlers in config/cqrs.php or add a mapping in handler_mappings."
        );
    }
}

