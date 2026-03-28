# Laravel CQRS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/samir-hussein/laravel-cqrs.svg?style=flat-square)](https://packagist.org/packages/samir-hussein/laravel-cqrs)
[![License](https://img.shields.io/packagist/l/samir-hussein/laravel-cqrs.svg?style=flat-square)](https://packagist.org/packages/samir-hussein/laravel-cqrs)

**CQRS (Command Query Responsibility Segregation) for Laravel** — separate writes (commands) from reads (queries), route both through a predictable pipeline (validation → middleware → handler), and resolve handlers automatically from naming conventions or explicit mappings.

This package gives you a **small, opinionated surface area**: two buses, one helper, convention-based handler resolution, optional Laravel validation on messages, and a **middleware pipeline** for cross-cutting behavior — without pulling in event sourcing or heavy infrastructure.

---

## Table of contents

- [Why use this package](#why-use-this-package)
- [What you get](#what-you-get)
- [Requirements](#requirements)
- [Installation](#installation)
- [Concepts](#concepts)
- [Quick start](#quick-start)
- [Handler resolution](#handler-resolution)
- [The `CQRS` helper](#the-cqrs-helper)
- [Command and query base classes](#command-and-query-base-classes)
- [Validation](#validation)
- [Middleware pipeline](#middleware-pipeline)
- [Using the buses directly](#using-the-buses-directly)
- [Automatic request data (controllers)](#automatic-request-data-controllers)
- [Configuration reference](#configuration-reference)
- [Artisan commands](#artisan-commands)
- [Package architecture](#package-architecture)
- [Exceptions](#exceptions)
- [License](#license)
- [Changelog](#changelog)

---

## Why use this package

- **Clear boundaries**: Commands change state; queries return data. That separation scales with team size and keeps controllers thin.
- **One entry point**: `CQRS::dispatch()` works for both commands and queries — validate once, then run the pipeline.
- **Laravel-native**: Uses the container, config, validator, and optional controller injection of `Command` / `Query` instances.
- **Extensible without clutter**: Global and per-message middleware for logging, DB transactions, authorization, caching reads, etc.
- **Flexible routing to handlers**: Convention by default; override specific handlers via config when names or folders do not match.

---

## What you get

| Area | Details |
|------|---------|
| **Core types** | `Command`, `Query` base classes with data access and validation hooks |
| **Buses** | `CommandBus`, `QueryBus` (singletons) resolving handlers and running the pipeline |
| **Facade-style API** | `LaravelCQRS\CQRS` static methods for dispatch with/without validation |
| **Contracts** | `CommandHandlerInterface`, `QueryHandlerInterface`, `MiddlewareInterface` |
| **Pipeline** | `Pipeline` executes middleware; middleware may be classes or container-resolved class names |
| **Config** | Namespaces, auto-resolve toggle, handler mappings, middleware stacks |
| **Generators** | `cqrs:command`, `cqrs:query`, `cqrs:handler`, `cqrs:middleware` |
| **Exceptions** | `HandlerNotFoundException`, `InvalidHandlerException` |

---

## Requirements

- PHP **≥ 8.0**
- Laravel **≥ 9** (`illuminate/*` packages as declared in `composer.json`)

---

## Installation

```bash
composer require samir-hussein/laravel-cqrs
```

Laravel will auto-discover `LaravelCQRS\CQRSServiceProvider`.

Publish configuration (recommended for production apps):

```bash
php artisan vendor:publish --tag=cqrs-config
```

This creates `config/cqrs.php`.

---

## Concepts

- **Command**: An intent to change application state (create user, place order). Typically handled once, side effects allowed.
- **Query**: A read model request (get user by id, list products). No state change in the CQRS sense; return a result.
- **Handler**: A class that implements `CommandHandlerInterface` or `QueryHandlerInterface` and contains the use-case logic.
- **Flow**: `Controller → CQRS::dispatch() → [validate] → CommandBus/QueryBus → middleware pipeline → handler.handle() → result`

```
HTTP Request
    → Controller
        → CQRS::dispatch($message)  // validates if rules() non-empty
            → Bus
                → Middleware (global, then per-message)
                    → Handler::handle($message)
                        → Domain / repositories / models
                            → mixed result
```

---

## Quick start

### 1. Command + handler

**Generate files:**

```bash
php artisan cqrs:command User/CreateUserCommand
php artisan cqrs:handler User/CreateUserCommandHandler --type=command
```

**Command** (`app/CQRS/Commands/User/CreateUserCommand.php`):

```php
<?php

namespace App\CQRS\Commands\User;

use LaravelCQRS\Command;

class CreateUserCommand extends Command
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}
```

**Handler** (`app/CQRS/Handlers/User/CreateUserCommandHandler.php`):

```php
<?php

namespace App\CQRS\Handlers\User;

use App\CQRS\Commands\User\CreateUserCommand;
use LaravelCQRS\Command;
use LaravelCQRS\Contracts\CommandHandlerInterface;

class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function handle(Command $command): mixed
    {
        /** @var CreateUserCommand $command */
        return $command->getData(); // replace with real persistence
    }
}
```

**Controller:**

```php
use App\CQRS\Commands\User\CreateUserCommand;
use LaravelCQRS\CQRS;

$user = CQRS::dispatch(new CreateUserCommand($request->all()));
```

### 2. Query + handler

```bash
php artisan cqrs:query User/GetUserQuery
php artisan cqrs:handler User/GetUserQueryHandler --type=query
```

Dispatch the same way:

```php
use App\CQRS\Queries\User\GetUserQuery;
use LaravelCQRS\CQRS;

$user = CQRS::dispatch(new GetUserQuery(['id' => $id]));
```

---

## Handler resolution

Resolution order:

1. **`config('cqrs.handler_mappings')`** — if the command/query class is a key, that handler class is used.
2. If **no mapping** and **`auto_resolve_handlers` is `false`**, a `HandlerNotFoundException` is thrown.
3. Otherwise **auto-resolution** uses `command_namespace`, `query_namespace`, and `handler_namespace` from config:
   - `App\CQRS\Commands\User\CreateUserCommand` → `App\CQRS\Handlers\User\CreateUserCommandHandler`
   - `App\CQRS\Queries\User\GetUserQuery` → `App\CQRS\Handlers\User\GetUserQueryHandler`
4. If namespaces do not match, the bus **falls back** to string replacement (`Commands` → `Handlers`, `Command` → `CommandHandler`, etc.).

Mappings always win — useful for legacy handlers or one-off overrides.

---

## The `CQRS` helper

All methods live on `LaravelCQRS\CQRS`.

| Method | Purpose |
|--------|---------|
| `dispatch(Command\|Query $m, bool $validate = true)` | Validates (if `$validate` and `rules()` not empty), then routes to `CommandBus` or `QueryBus`. |
| `dispatchWithoutValidation(Command\|Query $m)` | Same as `dispatch($m, false)`. |
| `dispatchCommand(Command $c, bool $validate = true)` | Command only. |
| `dispatchCommandWithoutValidation(Command $c)` | Command, no validation. |
| `dispatchQuery(Query $q, bool $validate = true)` | Query only. |
| `dispatchQueryWithoutValidation(Query $q)` | Query, no validation. |

Validation uses Laravel’s validator; failures throw `Illuminate\Validation\ValidationException`.

---

## Command and query base classes

`LaravelCQRS\Command` and `LaravelCQRS\Query` share the same data and validation API.

**Data:**

| Method | Description |
|--------|-------------|
| `getData(): array` | Full payload. |
| `get(string $key, mixed $default = null): mixed` | Single value. |
| `set(string $key, mixed $value): self` | Mutable payload (e.g. from middleware). |
| `has(string $key): bool` | Key isset. |
| `toObject(): object` | Recursive array → object (indexed arrays preserved as arrays). |

**Validation (override in subclasses):**

| Method | Default |
|--------|---------|
| `rules(): array` | `[]` — no rules means `validate()` returns raw data unchanged. |
| `messages(): array` | Custom rule messages. |
| `attributes(): array` | Custom attribute names for errors. |

**Execution:**

| Method | Description |
|--------|-------------|
| `validate(): array` | Runs validator; throws `ValidationException` on failure; returns validated data. |
| `isValid(): bool` | Non-throwing check. |
| `errors(): MessageBag` | Errors without throwing. |

---

## Validation

### Recommended: validate through `CQRS::dispatch()`

Define `rules()` (and optionally `messages()`, `attributes()`) on the command or query. Call:

```php
CQRS::dispatch(new CreateUserCommand($request->all()));
```

### Manual or layered checks

```php
$command = new CreateUserCommand($request->all());

if (! $command->isValid()) {
    return response()->json(['errors' => $command->errors()], 422);
}

$validated = $command->validate(); // or dispatch after manual validate()
```

### Validate inside a handler

```php
public function handle(Command $command): mixed
{
    $data = $command->validate();

    // use $data
}
```

### With Laravel Form Requests

You can validate at the HTTP layer and pass only validated data into the command:

```php
public function store(CreateUserRequest $request): JsonResponse
{
    $user = CQRS::dispatch(new CreateUserCommand($request->validated()));

    return response()->json($user, 201);
}
```

Use **Form Requests** for HTTP-specific rules; use **command/query `rules()`** for message invariants that belong with the use case.

---

## Middleware pipeline

Middleware wraps **handler** execution: global middleware first (in order), then middleware listed for that command/query class (in order). Implementation: `LaravelCQRS\Pipeline`.

### Interface

```php
use LaravelCQRS\Command;
use LaravelCQRS\Contracts\MiddlewareInterface;
use LaravelCQRS\Query;
use Closure;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Command|Query $commandOrQuery, Closure $next): mixed
    {
        logger()->info(get_class($commandOrQuery), $commandOrQuery->getData());

        return $next($commandOrQuery);
    }
}
```

Config accepts **class names**; the pipeline resolves them from the container. Instances may also be used if your stack provides them.

### Example: database transaction (commands only)

```php
use Illuminate\Support\Facades\DB;

public function handle(Command|Query $commandOrQuery, Closure $next): mixed
{
    if ($commandOrQuery instanceof Command) {
        return DB::transaction(fn () => $next($commandOrQuery));
    }

    return $next($commandOrQuery);
}
```

### Example: cache key from payload

Use `getData()` for serialization (there is no `toArray()` on the base classes):

```php
$key = 'cqrs:' . get_class($commandOrQuery) . ':' . md5(json_encode($commandOrQuery->getData()));
```

### Configuration

```php
// config/cqrs.php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
    ],
    \App\CQRS\Commands\User\CreateUserCommand::class => [
        \App\CQRS\Middleware\TransactionMiddleware::class,
    ],
],
```

Execution order: **global middleware → per-message middleware → handler**.

---

## Using the buses directly

Inject `LaravelCQRS\Bus\CommandBus` or `LaravelCQRS\Bus\QueryBus`. They do **not** run `CQRS::dispatch()` validation — validate yourself if needed:

```php
use LaravelCQRS\Bus\CommandBus;

public function __construct(private CommandBus $commandBus) {}

public function store(Request $request): JsonResponse
{
    $command = new CreateUserCommand($request->all());
    $command->validate();

    $result = $this->commandBus->dispatch($command);

    return response()->json($result, 201);
}
```

---

## Automatic request data (controllers)

When a `Command` or `Query` is **type-hinted** in a controller action and its internal `data` is still empty, the service provider merges the current request’s input and route parameters into the object. That lets you write:

```php
public function store(CreateUserCommand $command): JsonResponse
{
    return response()->json(CQRS::dispatch($command), 201);
}
```

If you construct the message manually with an array, that behavior is skipped. In console or when no request exists, injection is safely ignored.

---

## Configuration reference

Environment keys mirror `config/cqrs.php`:

| Key | Purpose |
|-----|---------|
| `handler_namespace` | Base namespace for handler classes (`App\CQRS\Handlers`). |
| `command_namespace` | Base namespace for commands (`App\CQRS\Commands`). |
| `query_namespace` | Base namespace for queries (`App\CQRS\Queries`). |
| `middleware_namespace` | Used by `cqrs:middleware` for default output namespace (`App\CQRS\Middleware`). |
| `auto_resolve_handlers` | `true`: resolve by convention/mapping; `false`: mappings required (or exception). |
| `handler_mappings` | `['Full\\Command\\Class' => 'Full\\Handler\\Class', ...]` — highest priority. |
| `middleware` | `global` array + optional keys per **fully qualified** command/query class name. |

Example `.env` overrides:

```env
CQRS_HANDLER_NAMESPACE=App\CQRS\Handlers
CQRS_COMMAND_NAMESPACE=App\CQRS\Commands
CQRS_QUERY_NAMESPACE=App\CQRS\Queries
CQRS_MIDDLEWARE_NAMESPACE=App\CQRS\Middleware
CQRS_AUTO_RESOLVE=true
```

---

## Artisan commands

| Command | Generates |
|---------|-----------|
| `php artisan cqrs:command User/CreateUserCommand` | `app/CQRS/Commands/User/CreateUserCommand.php` |
| `php artisan cqrs:query User/GetUserQuery` | `app/CQRS/Queries/User/GetUserQuery.php` |
| `php artisan cqrs:handler User/CreateUserCommandHandler --type=command` | Command handler implementing `CommandHandlerInterface` |
| `php artisan cqrs:handler User/GetUserQueryHandler --type=query` | Query handler implementing `QueryHandlerInterface` |
| `php artisan cqrs:middleware User/TransactionMiddleware` | Middleware implementing `MiddlewareInterface` |

Namespaces follow `config/cqrs.php` (`handler_namespace`, etc.).

---

## Package architecture

```
src/
├── Bus/
│   ├── AbstractBus.php      # Handler resolution, middleware collection, pipeline execution
│   ├── CommandBus.php
│   └── QueryBus.php
├── Console/                 # make:* commands + stubs
├── Contracts/
│   ├── CommandHandlerInterface.php
│   ├── QueryHandlerInterface.php
│   └── MiddlewareInterface.php
├── Exceptions/
│   ├── HandlerNotFoundException.php
│   └── InvalidHandlerException.php
├── Command.php
├── Query.php
├── CQRS.php                 # Static dispatch helpers
├── Pipeline.php             # Middleware stack
└── CQRSServiceProvider.php
config/
└── cqrs.php
```

**Namespaces in your app** (typical):

```
app/CQRS/
├── Commands/...
├── Queries/...
├── Handlers/...
└── Middleware/...
```

---

## Exceptions

| Exception | When |
|-----------|------|
| `HandlerNotFoundException` | Handler class missing, or auto-resolve off with no mapping. |
| `InvalidHandlerException` | Resolved class does not implement the expected handler interface. |
| `Illuminate\Validation\ValidationException` | Failed `validate()` or `CQRS::dispatch(..., true)`. |

---

## License

MIT. See the [`LICENSE`](LICENSE) file in the repository.

---

## Changelog

### 1.0.0

- Initial release: `Command` / `Query` with Laravel validation integration.
- `CommandBus` / `QueryBus` with configurable handler resolution and handler mappings.
- `CQRS` helper for unified dispatch with optional validation.
- Middleware pipeline (`Pipeline`, `MiddlewareInterface`) with global and per-message config.
- `HandlerNotFoundException`, `InvalidHandlerException`.
- Service provider: singleton buses, controller injection for empty commands/queries, Artisan generators.
- Configuration: namespaces, `auto_resolve_handlers`, `handler_mappings`, `middleware`.

Future versions will be listed here following [Semantic Versioning](https://semver.org/) and [Keep a Changelog](https://keepachangelog.com/) principles.
