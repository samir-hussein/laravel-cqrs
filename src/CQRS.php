<?php

namespace LaravelCQRS;

use LaravelCQRS\Bus\CommandBus;
use LaravelCQRS\Bus\QueryBus;

/**
 * CQRS Helper Class
 * 
 * Provides convenient static methods for dispatching commands and queries
 * with automatic validation.
 */
class CQRS
{
    /**
     * Dispatch a command or query with automatic validation.
     * Automatically determines the type and uses the appropriate bus.
     * 
     * @param Command|Query $commandOrQuery
     * @param bool $validate Whether to validate before dispatching
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function dispatch(Command|Query $commandOrQuery, bool $validate = true): mixed
    {
        if ($validate) {
            $commandOrQuery->validate();
        }

        if ($commandOrQuery instanceof Command) {
            return \app(CommandBus::class)->dispatch($commandOrQuery);
        }

        return \app(QueryBus::class)->dispatch($commandOrQuery);
    }

    /**
     * Dispatch a command with automatic validation.
     * 
     * @param Command $command
     * @param bool $validate Whether to validate the command before dispatching
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function dispatchCommand(Command $command, bool $validate = true): mixed
    {
        if ($validate) {
            $command->validate();
        }

        return \app(CommandBus::class)->dispatch($command);
    }

    /**
     * Dispatch a query with automatic validation.
     * 
     * @param Query $query
     * @param bool $validate Whether to validate the query before dispatching
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function dispatchQuery(Query $query, bool $validate = true): mixed
    {
        if ($validate) {
            $query->validate();
        }

        return \app(QueryBus::class)->dispatch($query);
    }

    /**
     * Dispatch a command or query without validation.
     * 
     * @param Command|Query $commandOrQuery
     * @return mixed
     */
    public static function dispatchWithoutValidation(Command|Query $commandOrQuery): mixed
    {
        return static::dispatch($commandOrQuery, false);
    }

    /**
     * Dispatch a command without validation.
     * 
     * @param Command $command
     * @return mixed
     */
    public static function dispatchCommandWithoutValidation(Command $command): mixed
    {
        return static::dispatchCommand($command, false);
    }

    /**
     * Dispatch a query without validation.
     * 
     * @param Query $query
     * @return mixed
     */
    public static function dispatchQueryWithoutValidation(Query $query): mixed
    {
        return static::dispatchQuery($query, false);
    }
}

