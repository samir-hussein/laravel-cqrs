# Middleware Usage Guide

This guide explains how to use middleware with commands and queries for cross-cutting concerns like logging, transactions, authorization, etc.

## Overview

Middleware allows you to wrap command/query handler execution to add functionality that applies across multiple handlers. Common use cases include:

- **Logging**: Log all command/query executions
- **Transactions**: Wrap database operations in transactions
- **Authorization**: Check permissions before execution
- **Performance Monitoring**: Track execution time
- **Caching**: Cache query results

## Creating Middleware

### Using the Artisan Command

Generate a new CQRS pipeline middleware with:

```bash
# Creates app/CQRS/Middleware/TransactionMiddleware.php
php artisan cqrs:middleware TransactionMiddleware

# With subfolder (creates app/CQRS/Middleware/User/TransactionMiddleware.php)
php artisan cqrs:middleware User/TransactionMiddleware
```

The generated class implements `LaravelCQRS\Contracts\MiddlewareInterface` and includes a `handle()` stub. Add your logic before/after the `$next($commandOrQuery)` call.

The default namespace is `App\CQRS\Middleware`. You can change it in `config/cqrs.php`:

```php
'middleware_namespace' => env('CQRS_MIDDLEWARE_NAMESPACE', 'App\\CQRS\\Middleware'),
```

### Basic Middleware Structure

Create a middleware class that implements `MiddlewareInterface` (or use the stub from `cqrs:middleware`):

```php
<?php

namespace App\CQRS\Middleware;

use LaravelCQRS\Command;
use LaravelCQRS\Contracts\MiddlewareInterface;
use LaravelCQRS\Query;
use Closure;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        $class = get_class($commandOrQuery);
        
        // Before execution
        \Log::info("Executing: {$class}", $commandOrQuery->toArray());
        
        $startTime = microtime(true);
        
        try {
            // Execute the handler
            $result = $next($commandOrQuery);
            
            // After execution
            $executionTime = (microtime(true) - $startTime) * 1000;
            \Log::info("Completed: {$class} in {$executionTime}ms");
            
            return $result;
        } catch (\Exception $e) {
            \Log::error("Failed: {$class}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
```

## Example Middleware

### Transaction Middleware

Wrap database operations in a transaction:

```php
<?php

namespace App\CQRS\Middleware;

use LaravelCQRS\Command;
use LaravelCQRS\Contracts\MiddlewareInterface;
use LaravelCQRS\Query;
use Closure;
use Illuminate\Support\Facades\DB;

class TransactionMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        // Only wrap commands in transactions (queries are read-only)
        if ($commandOrQuery instanceof Command) {
            return DB::transaction(function () use ($commandOrQuery, $next) {
                return $next($commandOrQuery);
            });
        }
        
        // For queries, just pass through
        return $next($commandOrQuery);
    }
}
```

### Authorization Middleware

Check permissions before execution:

```php
<?php

namespace App\CQRS\Middleware;

use App\CQRS\Commands\User\CreateUserCommand;
use LaravelCQRS\Command;
use LaravelCQRS\Contracts\MiddlewareInterface;
use LaravelCQRS\Query;
use Closure;
use Illuminate\Support\Facades\Gate;

class AuthorizationMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        // Example: Check if user can create users
        if ($commandOrQuery instanceof CreateUserCommand) {
            if (!Gate::allows('create', \App\Models\User::class)) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'You do not have permission to create users.'
                );
            }
        }
        
        return $next($commandOrQuery);
    }
}
```

### Caching Middleware

Cache query results:

```php
<?php

namespace App\CQRS\Middleware;

use LaravelCQRS\Command;
use LaravelCQRS\Contracts\MiddlewareInterface;
use LaravelCQRS\Query;
use Closure;
use Illuminate\Support\Facades\Cache;

class CacheMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        // Only cache queries, not commands
        if ($commandOrQuery instanceof Query) {
            $cacheKey = 'cqrs:' . get_class($commandOrQuery) . ':' . md5(serialize($commandOrQuery->toArray()));
            
            return Cache::remember($cacheKey, 3600, function () use ($commandOrQuery, $next) {
                return $next($commandOrQuery);
            });
        }
        
        return $next($commandOrQuery);
    }
}
```

## Configuring Middleware

### Global Middleware

Apply middleware to all commands/queries by adding it to the `global` array in `config/cqrs.php`:

```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
        \App\CQRS\Middleware\TransactionMiddleware::class,
    ],
],
```

### Command/Query Specific Middleware

Apply middleware to specific commands/queries:

```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
    ],
    'App\CQRS\Commands\User\CreateUserCommand' => [
        \App\CQRS\Middleware\AuthorizationMiddleware::class,
    ],
    'App\CQRS\Queries\User\GetUserQuery' => [
        \App\CQRS\Middleware\CacheMiddleware::class,
    ],
],
```

### Complete Configuration Example

```php
// config/cqrs.php

return [
    // ... other config ...
    
    'middleware' => [
        // Global middleware applies to all commands/queries
        'global' => [
            \App\CQRS\Middleware\LoggingMiddleware::class,
        ],
        
        // Command-specific middleware
        'App\CQRS\Commands\User\CreateUserCommand' => [
            \App\CQRS\Middleware\TransactionMiddleware::class,
            \App\CQRS\Middleware\AuthorizationMiddleware::class,
        ],
        
        'App\CQRS\Commands\User\UpdateUserCommand' => [
            \App\CQRS\Middleware\TransactionMiddleware::class,
            \App\CQRS\Middleware\AuthorizationMiddleware::class,
        ],
        
        // Query-specific middleware
        'App\CQRS\Queries\User\GetUserQuery' => [
            \App\CQRS\Middleware\CacheMiddleware::class,
        ],
    ],
];
```

## Middleware Execution Order

Middleware is executed in the following order:

1. **Global middleware** (in the order defined)
2. **Command/Query specific middleware** (in the order defined)
3. **Handler execution**

The middleware stack wraps the handler, so execution flows like this:

```
Request → Middleware 1 (before) → Middleware 2 (before) → Handler → Middleware 2 (after) → Middleware 1 (after) → Response
```

## Best Practices

1. **Keep middleware focused**: Each middleware should handle one concern
2. **Use global middleware for common concerns**: Logging, transactions, etc.
3. **Use specific middleware for special cases**: Authorization, caching for specific queries
4. **Handle exceptions properly**: Don't swallow exceptions unless you have a good reason
5. **Consider performance**: Middleware adds overhead, so use it judiciously
6. **Test middleware**: Write tests for your middleware to ensure they work correctly

## Advanced Usage

### Conditional Middleware

You can make middleware conditional based on the command/query data:

```php
public function handle(Command|Query $commandOrQuery, Closure $next): mixed
{
    // Only apply to commands with certain data
    if ($commandOrQuery instanceof CreateUserCommand && $commandOrQuery->get('role') === 'admin') {
        // Special handling for admin creation
    }
    
    return $next($commandOrQuery);
}
```

### Modifying Commands/Queries

Middleware can modify commands/queries before they reach the handler:

```php
public function handle(Command|Query $commandOrQuery, Closure $next): mixed
{
    // Add timestamp to command
    if ($commandOrQuery instanceof Command) {
        $commandOrQuery->set('executed_at', now());
    }
    
    return $next($commandOrQuery);
}
```

### Short-circuiting Execution

Middleware can prevent handler execution:

```php
public function handle(Command|Query $commandOrQuery, Closure $next): mixed
{
    // Check if already processed
    if ($commandOrQuery->has('processed')) {
        return $commandOrQuery->get('result');
    }
    
    $result = $next($commandOrQuery);
    
    // Mark as processed
    $commandOrQuery->set('processed', true);
    $commandOrQuery->set('result', $result);
    
    return $result;
}
```

## Testing Middleware

Example test for middleware:

```php
<?php

namespace Tests\CQRS\Middleware;

use App\CQRS\Commands\User\CreateUserCommand;
use App\CQRS\Middleware\LoggingMiddleware;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoggingMiddlewareTest extends TestCase
{
    public function test_it_logs_command_execution(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Executing: ' . CreateUserCommand::class, \Mockery::type('array'));
        
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Completed: .* in .*ms/'));
        
        $middleware = new LoggingMiddleware();
        $command = new CreateUserCommand(['name' => 'Test']);
        
        // Mock the next closure
        $next = function ($command) {
            return 'result';
        };
        
        $result = $middleware->handle($command, $next);
        
        $this->assertEquals('result', $result);
    }
}
```

## Common Patterns

### Performance Monitoring

```php
class PerformanceMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        $start = microtime(true);
        $memoryStart = memory_get_usage();
        
        $result = $next($commandOrQuery);
        
        $duration = (microtime(true) - $start) * 1000;
        $memoryUsed = memory_get_usage() - $memoryStart;
        
        if ($duration > 1000) { // Log slow operations
            \Log::warning('Slow CQRS operation', [
                'class' => get_class($commandOrQuery),
                'duration_ms' => $duration,
                'memory_bytes' => $memoryUsed,
            ]);
        }
        
        return $result;
    }
}
```

### Rate Limiting

```php
class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        $key = 'cqrs:rate_limit:' . get_class($commandOrQuery) . ':' . auth()->id();
        
        if (\RateLimiter::tooManyAttempts($key, 10)) {
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException();
        }
        
        \RateLimiter::hit($key, 60); // 10 per minute
        
        return $next($commandOrQuery);
    }
}
```

