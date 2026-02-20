<?php

namespace LaravelCQRS\Contracts;

use LaravelCQRS\Command;
use LaravelCQRS\Query;
use Closure;

/**
 * Middleware Interface
 * 
 * Middleware can wrap command/query handler execution for cross-cutting concerns
 * like logging, transactions, authorization, etc.
 */
interface MiddlewareInterface
{
    /**
     * Handle the command/query and call the next middleware in the pipeline.
     *
     * @param Command|Query $commandOrQuery
     * @param Closure $next
     * @return mixed
     */
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed;
}

