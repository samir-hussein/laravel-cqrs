<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CQRS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the CQRS package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Handler Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace where handlers are located. This is used to resolve
    | handler classes from commands and queries.
    |
    */

    'handler_namespace' => env('CQRS_HANDLER_NAMESPACE', 'App\\CQRS\\Handlers'),

    /*
    |--------------------------------------------------------------------------
    | Command Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace where commands are located.
    |
    */

    'command_namespace' => env('CQRS_COMMAND_NAMESPACE', 'App\\CQRS\\Commands'),

    /*
    |--------------------------------------------------------------------------
    | Query Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace where queries are located.
    |
    */

    'query_namespace' => env('CQRS_QUERY_NAMESPACE', 'App\\CQRS\\Queries'),

    /*
    |--------------------------------------------------------------------------
    | Middleware Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace where CQRS pipeline middleware classes are located.
    | Used by the cqrs:middleware Artisan command.
    |
    */

    'middleware_namespace' => env('CQRS_MIDDLEWARE_NAMESPACE', 'App\\CQRS\\Middleware'),

    /*
    |--------------------------------------------------------------------------
    | Auto-resolve Handlers
    |--------------------------------------------------------------------------
    |
    | When enabled, handlers are automatically resolved based on naming
    | conventions. When disabled, you must manually bind handlers.
    |
    */

    'auto_resolve_handlers' => env('CQRS_AUTO_RESOLVE', true),

    /*
    |--------------------------------------------------------------------------
    | Handler Mappings
    |--------------------------------------------------------------------------
    |
    | Manual mappings for commands/queries to handlers. These mappings take
    | precedence over auto-resolution, allowing you to override specific handlers
    | even when auto_resolve_handlers is enabled.
    |
    | Use cases:
    | - Custom handler locations for specific commands/queries
    | - Override default handler resolution for edge cases
    | - Works alongside auto-resolution (checked first, then falls back to auto)
    |
    | Example:
    | 'handler_mappings' => [
    |     'App\CQRS\Commands\User\CreateUserCommand' => 'App\Custom\Handlers\CreateUserHandler',
    |     'App\CQRS\Queries\User\GetUserQuery' => 'App\Legacy\Handlers\GetUserHandler',
    | ],
    |
    */

    'handler_mappings' => [],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware can wrap command/query handler execution for cross-cutting
    | concerns like logging, transactions, authorization, etc.
    |
    | Global middleware applies to all commands/queries.
    | Specific middleware can be defined per command/query class.
    |
    | Example:
    | 'middleware' => [
    |     'global' => [
    |         \App\CQRS\Middleware\LoggingMiddleware::class,
    |         \App\CQRS\Middleware\TransactionMiddleware::class,
    |     ],
    |     'App\CQRS\Commands\User\CreateUserCommand' => [
    |         \App\CQRS\Middleware\AuthorizationMiddleware::class,
    |     ],
    | ],
    |
    */

    'middleware' => [
        'global' => [],
    ],
];

