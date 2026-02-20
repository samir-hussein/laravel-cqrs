<?php

namespace LaravelCQRS\Exceptions;

use RuntimeException;

class InvalidHandlerException extends RuntimeException
{
    /**
     * Create a new invalid handler exception.
     *
     * @param string $handlerClass
     * @param string $expectedInterface
     * @return static
     */
    public static function for(string $handlerClass, string $expectedInterface): static
    {
        return new static(
            "Handler {$handlerClass} must implement {$expectedInterface}"
        );
    }
}

