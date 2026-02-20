# Laravel CQRS Package

A clean and simple CQRS (Command Query Responsibility Segregation) pattern implementation for Laravel applications.

## Features

- ✅ **Simple & Clean**: Easy to understand and implement
- ✅ **Auto-resolution**: Handlers are automatically resolved based on naming conventions
- ✅ **Laravel Integration**: Seamless integration with Laravel's service container
- ✅ **Type-safe**: Full type hints and interfaces
- ✅ **Flexible**: Works with any Laravel project structure
- ✅ **Validation Support**: Built-in validation methods for commands and queries
- ✅ **Middleware Pipeline**: Wrap handlers with middleware for logging, transactions, authorization, etc.
- ✅ **Universal Dispatch**: Single `dispatch()` method that works with both Commands and Queries

## Installation

### Step 1: Install via Composer

```bash
composer require samir-hussein/laravel-cqrs
```

The package will be automatically discovered by Laravel.

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=cqrs-config
```

This creates `config/cqrs.php` where you can configure namespaces, middleware, and handler mappings.

### Step 3: Create Directory Structure (Optional)

The package will create directories automatically when you use Artisan commands. Or create them manually:

```
app/
├── CQRS/
│   ├── Commands/
│   ├── Queries/
│   └── Handlers/
```

### Step 4: Use Artisan Commands (Recommended)

The package includes Artisan commands to generate CQRS files:

```bash
# Create a command
php artisan cqrs:command User/CreateUserCommand

# Create a query
php artisan cqrs:query User/GetUserQuery

# Create a command handler
php artisan cqrs:handler User/CreateUserCommandHandler --type=command

# Create a query handler
php artisan cqrs:handler User/GetUserQueryHandler --type=query

# Create a pipeline middleware
php artisan cqrs:middleware User/TransactionMiddleware
```

That's it! You're ready to use the package.

## Example 1: Creating and Using a Command

This example shows how to create a command with validation and middleware support.

### 1. Create the Command

```php
<?php

namespace App\CQRS\Commands\User;

use LaravelCQRS\Command;

class CreateUserCommand extends Command
{
    /**
     * Create a new command instance.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function getPassword(): ?string
    {
        return $this->get('password');
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Custom validation messages (optional)
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
```

### 2. Create the Command Handler

**Using Artisan (Recommended):**
```bash
php artisan cqrs:handler User/CreateUserCommandHandler --type=command
```

**Or manually create the file:**

```php
<?php

namespace App\CQRS\Handlers\User;

use App\Models\User;
use App\Repositories\UserRepository;
use LaravelCQRS\Command;
use LaravelCQRS\Commands\User\CreateUserCommand;
use LaravelCQRS\Contracts\CommandHandlerInterface;
use Illuminate\Support\Facades\Hash;

class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function handle(Command $command): User
    {
        /** @var CreateUserCommand $command */
        $data = $command->getData();
        
        // Hash password before creating user
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->create($data);
    }
}
```

### 3. Configure Middleware (Optional)

Add middleware in `config/cqrs.php`:

```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
    ],
    'App\CQRS\Commands\User\CreateUserCommand' => [
        \App\CQRS\Middleware\TransactionMiddleware::class,
        \App\CQRS\Middleware\AuthorizationMiddleware::class,
    ],
],
```

### 4. Use in Controller

```php
<?php

namespace App\Http\Controllers;

use App\CQRS\Commands\User\CreateUserCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelCQRS\CQRS;

class UserController extends Controller
{
    // Option 1: Manual instantiation (traditional way)
    public function store(Request $request): JsonResponse
    {
        // CQRS::dispatch() automatically:
        // 1. Validates the command (using rules() method)
        // 2. Applies middleware (if configured)
        // 3. Dispatches to the handler
        $user = CQRS::dispatch(new CreateUserCommand($request->all()));

        return response()->json($user, 201);
    }

    // Option 2: Automatic dependency injection (recommended)
    public function create(CreateUserCommand $command): JsonResponse
    {
        // The command is automatically instantiated with request data
        // No need to manually pass $request->all()
        $user = CQRS::dispatch($command);

        return response()->json($user, 201);
    }
}
```

**What happens:**
1. ✅ **Validation**: Command data is validated using `rules()` method
2. ✅ **Middleware**: Global and command-specific middleware are executed
3. ✅ **Handler**: `CreateUserCommandHandler` processes the command
4. ✅ **Response**: User is created and returned

## Example 2: Creating and Using a Query

This example shows how to create a query with validation and middleware support.

### 1. Create the Query

**Using Artisan (Recommended):**
```bash
php artisan cqrs:query User/GetUserQuery
```

**Or manually create the file:**

```php
<?php

namespace App\CQRS\Queries\User;

use LaravelCQRS\Query;

class GetUserQuery extends Query
{
    /**
     * Create a new query instance.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getId(): int|string|null
    {
        return $this->get('id');
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom validation messages (optional)
     */
    public function messages(): array
    {
        return [
            'id.required' => 'User ID is required.',
            'id.integer' => 'User ID must be a valid number.',
        ];
    }
}
```

### 2. Create the Query Handler

**Using Artisan (Recommended):**
```bash
php artisan cqrs:handler User/GetUserQueryHandler --type=query
```

**Or manually create the file:**

```php
<?php

namespace App\CQRS\Handlers\User;

use App\Models\User;
use App\Repositories\UserRepository;
use LaravelCQRS\Contracts\QueryHandlerInterface;
use LaravelCQRS\Query;
use LaravelCQRS\Queries\User\GetUserQuery;

class GetUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function handle(Query $query): ?User
    {
        /** @var GetUserQuery $query */
        return $this->userRepository->find($query->getId());
    }
}
```

### 3. Configure Middleware (Optional)

Add middleware in `config/cqrs.php`:

```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
    ],
    'App\CQRS\Queries\User\GetUserQuery' => [
        \App\CQRS\Middleware\CacheMiddleware::class, // Cache query results
    ],
],
```

### 4. Use in Controller

```php
<?php

namespace App\Http\Controllers;

use App\CQRS\Queries\User\GetUserQuery;
use Illuminate\Http\JsonResponse;
use LaravelCQRS\CQRS;

class UserController extends Controller
{
    public function show(string $id): JsonResponse
    {
        // CQRS::dispatch() automatically:
        // 1. Validates the query (using rules() method)
        // 2. Applies middleware (if configured - e.g., caching)
        // 3. Dispatches to the handler
        // Option 1: Manual instantiation
        $user = CQRS::dispatch(new GetUserQuery(['id' => $id]));

        // Option 2: Automatic dependency injection (recommended)
        // public function show(GetUserQuery $query): JsonResponse
        // {
        //     $user = CQRS::dispatch($query);
        //     return response()->json($user);
        // }

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }
}
```

**What happens:**
1. ✅ **Validation**: Query parameters are validated using `rules()` method
2. ✅ **Middleware**: Global and query-specific middleware are executed (e.g., caching)
3. ✅ **Handler**: `GetUserQueryHandler` processes the query
4. ✅ **Response**: User data is returned (or 404 if not found)

## Validation

Commands and Queries support built-in validation. Override these methods in your command/query classes:

- `rules(): array` - Define validation rules
- `messages(): array` - Custom error messages (optional)
- `attributes(): array` - Custom attribute names (optional)

The `CQRS::dispatch()` method automatically validates before dispatching. If validation fails, a `ValidationException` is thrown.

**Manual validation:**
```php
$command = new CreateUserCommand($request->all());

// Check if valid
if ($command->isValid()) {
    // Get errors
    $errors = $command->errors();
}

// Or validate and get validated data
$validatedData = $command->validate();
```

See [VALIDATION_USAGE.md](VALIDATION_USAGE.md) for complete validation guide.

## Middleware

Middleware allows you to wrap handler execution for cross-cutting concerns:

- **Logging**: Log all command/query executions
- **Transactions**: Wrap database operations in transactions
- **Authorization**: Check permissions before execution
- **Caching**: Cache query results
- **Performance Monitoring**: Track execution time

**Create middleware:**
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
        if ($commandOrQuery instanceof Command) {
            return DB::transaction(function () use ($commandOrQuery, $next) {
                return $next($commandOrQuery);
            });
        }
        
        return $next($commandOrQuery);
    }
}
```

**Configure in `config/cqrs.php`:**
```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
    ],
    'App\CQRS\Commands\User\CreateUserCommand' => [
        \App\CQRS\Middleware\TransactionMiddleware::class,
    ],
],
```

See [MIDDLEWARE_USAGE.md](MIDDLEWARE_USAGE.md) for complete middleware guide.

## Naming Conventions

The package automatically resolves handlers based on naming:

- **Command**: `App\CQRS\Commands\User\CreateUserCommand`
- **Handler**: `App\CQRS\Handlers\User\CreateUserCommandHandler`

- **Query**: `App\CQRS\Queries\User\GetUserQuery`
- **Handler**: `App\CQRS\Handlers\User\GetUserQueryHandler`

## Configuration

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --tag=cqrs-config
```

This creates `config/cqrs.php` with all available options. Here's a detailed explanation of each configuration option:

### Handler Namespace

```php
'handler_namespace' => env('CQRS_HANDLER_NAMESPACE', 'App\\CQRS\\Handlers'),
```

The base namespace where your handler classes are located. Used for auto-resolution when handlers follow the naming convention.

**Example:** If your handler is at `App\CQRS\Handlers\User\CreateUserCommandHandler`, set this to `App\CQRS\Handlers`.

### Command Namespace

```php
'command_namespace' => env('CQRS_COMMAND_NAMESPACE', 'App\\CQRS\\Commands'),
```

The base namespace where your command classes are located. Used to determine if a class is a command for handler resolution.

**Example:** If your commands are at `App\CQRS\Commands\User\CreateUserCommand`, set this to `App\CQRS\Commands`.

### Query Namespace

```php
'query_namespace' => env('CQRS_QUERY_NAMESPACE', 'App\\CQRS\\Queries'),
```

The base namespace where your query classes are located. Used to determine if a class is a query for handler resolution.

**Example:** If your queries are at `App\CQRS\Queries\User\GetUserQuery`, set this to `App\CQRS\Queries`.

### Auto-resolve Handlers

```php
'auto_resolve_handlers' => env('CQRS_AUTO_RESOLVE', true),
```

When `true` (default), handlers are automatically resolved based on naming conventions. When `false`, you must provide handler mappings for all commands/queries, otherwise an exception will be thrown.

**Use cases:**
- Set to `false` if you want explicit control over handler resolution
- Set to `true` for automatic resolution (recommended for most cases)

### Handler Mappings

```php
'handler_mappings' => [
    'App\CQRS\Commands\User\CreateUserCommand' => 'App\Custom\Handlers\CreateUserHandler',
    'App\CQRS\Queries\User\GetUserQuery' => 'App\Legacy\Handlers\GetUserHandler',
],
```

Manual mappings for commands/queries to handlers. These mappings **always take precedence** over auto-resolution, even when `auto_resolve_handlers` is `true`.

**Use cases:**
- Custom handler locations that don't follow naming conventions
- Override default handler resolution for specific commands/queries
- Legacy handlers in different namespaces
- Works alongside auto-resolution (checked first, then falls back to auto)

**Important:** If `auto_resolve_handlers` is `false` and no mapping exists for a command/query, a `HandlerNotFoundException` will be thrown.

### Middleware

```php
'middleware' => [
    'global' => [
        \App\CQRS\Middleware\LoggingMiddleware::class,
        \App\CQRS\Middleware\TransactionMiddleware::class,
    ],
    'App\CQRS\Commands\User\CreateUserCommand' => [
        \App\CQRS\Middleware\AuthorizationMiddleware::class,
    ],
    'App\CQRS\Queries\User\GetUserQuery' => [
        \App\CQRS\Middleware\CacheMiddleware::class,
    ],
],
```

Configure middleware for commands and queries:

- **`global`**: Middleware that applies to all commands and queries
- **Command/Query specific**: Middleware that applies only to a specific command or query class

**Execution order:** Global middleware runs first, then command/query-specific middleware.

**Example middleware use cases:**
- **Logging**: Log all command/query executions
- **Transactions**: Wrap database operations in transactions
- **Authorization**: Check permissions before execution
- **Caching**: Cache query results
- **Performance Monitoring**: Track execution time

See [MIDDLEWARE_USAGE.md](MIDDLEWARE_USAGE.md) for complete middleware guide.

### Environment Variables

You can override configuration values using environment variables in your `.env` file:

```env
CQRS_HANDLER_NAMESPACE=App\CQRS\Handlers
CQRS_COMMAND_NAMESPACE=App\CQRS\Commands
CQRS_QUERY_NAMESPACE=App\CQRS\Queries
CQRS_AUTO_RESOLVE=true
```

## Artisan Commands

The package includes convenient Artisan commands to generate CQRS files:

### Create a Command

```bash
php artisan cqrs:command User/CreateUserCommand
```

This creates: `app/CQRS/Commands/User/CreateUserCommand.php`

### Create a Query

```bash
php artisan cqrs:query User/GetUserQuery
```

This creates: `app/CQRS/Queries/User/GetUserQuery.php`

### Create a Handler

```bash
# Create a command handler
php artisan cqrs:handler User/CreateUserCommandHandler --type=command

# Create a query handler
php artisan cqrs:handler User/GetUserQueryHandler --type=query
```

This creates:
- `app/CQRS/Handlers/User/CreateUserCommandHandler.php` (for commands)
- `app/CQRS/Handlers/User/GetUserQueryHandler.php` (for queries)

**Note:** The `--type` option is required for handlers to determine which interface and base class to use.

### Create a Middleware

```bash
php artisan cqrs:middleware User/TransactionMiddleware
```

This creates: `app/CQRS/Middleware/User/TransactionMiddleware.php` (implements `LaravelCQRS\Contracts\MiddlewareInterface`). Register it in `config/cqrs.php` under `middleware.global` or per command/query.

## Using Buses Directly

You can also use the buses directly instead of the `CQRS` helper:

```php
use LaravelCQRS\Bus\CommandBus;
use LaravelCQRS\Bus\QueryBus;

// In controller
public function __construct(
    private CommandBus $commandBus,
    private QueryBus $queryBus
) {}

public function store(Request $request): JsonResponse
{
    $command = new CreateUserCommand($request->all());
    $command->validate(); // Manual validation
    $user = $this->commandBus->dispatch($command);
    return response()->json($user, 201);
}
```

## Architecture Flow

```
Route → Controller → CQRS::dispatch() → Validation → Middleware Pipeline → Handler → Repository
```

## Requirements

- PHP >= 8.0
- Laravel >= 9.0

## License

MIT

## Support

For issues and questions, please open an issue on GitHub.
