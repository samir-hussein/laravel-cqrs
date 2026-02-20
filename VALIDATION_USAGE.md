# Validation Usage Guide

This guide explains how to use the validation methods in `Command` and `Query` classes.

## Overview

The validation methods (`rules()`, `messages()`, `attributes()`) allow you to define validation rules directly in your Command/Query classes. This keeps validation logic close to your data structures.

## CQRS Helper Methods

### `CQRS::dispatch(Command|Query $commandOrQuery, bool $validate = true)` ⭐ **Recommended**

Universal dispatch method that automatically detects Command or Query and dispatches appropriately. By default, validation is enabled.

```php
use LaravelCQRS\CQRS;

// Works with Commands
$user = CQRS::dispatch(new CreateUserCommand($request->all()));

// Works with Queries
$user = CQRS::dispatch(new GetUserQuery(['id' => $id]));

// Without validation
$user = CQRS::dispatchWithoutValidation(new CreateUserCommand($request->all()));
```

### `CQRS::dispatchCommand(Command $command, bool $validate = true)`

Explicitly dispatch a command (alternative to `dispatch()`).

```php
use LaravelCQRS\CQRS;

// With validation (default)
$user = CQRS::dispatchCommand(new CreateUserCommand($request->all()));

// Without validation
$user = CQRS::dispatchCommandWithoutValidation(new CreateUserCommand($request->all()));
```

### `CQRS::dispatchQuery(Query $query, bool $validate = true)`

Explicitly dispatch a query (alternative to `dispatch()`).

```php
use LaravelCQRS\CQRS;

// With validation (default)
$user = CQRS::dispatchQuery(new GetUserQuery(['id' => $id]));

// Without validation
$user = CQRS::dispatchQueryWithoutValidation(new GetUserQuery(['id' => $id]));
```

## Basic Usage

### 1. Define Validation Rules in Your Command

Override the `rules()` method in your command class:

```php
<?php

namespace App\CQRS\Commands\User;

use LaravelCQRS\Command;

class CreateUserCommand extends Command
{
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
     * Define validation rules for this command
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
     * Optional: Custom validation messages
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Optional: Custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
        ];
    }
}
```

### 2. Validate in Your Controller

#### Option A: Validate and Throw Exception (Recommended)

```php
<?php

namespace App\Http\Controllers;

use App\CQRS\Commands\User\CreateUserCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelCQRS\Bus\CommandBus;

class UserController extends Controller
{
    public function __construct(
        private CommandBus $commandBus
    ) {}

    public function store(Request $request): JsonResponse
    {
        $command = new CreateUserCommand($request->all());
        
        // Validate and dispatch in one call
        $user = \LaravelCQRS\CQRS::dispatch($command);

        return response()->json($user, 201);
    }
}
```

#### Option B: Check Validation Without Exception

```php
public function store(Request $request): JsonResponse
{
    $command = new CreateUserCommand($request->all());
    
    // Check if valid without throwing exception
    if (!$command->isValid()) {
        return response()->json([
            'errors' => $command->errors()
        ], 422);
    }
    
    $user = $this->commandBus->dispatch($command);

    return response()->json($user, 201);
}
```

#### Option C: Validate in Handler

You can also validate inside your handler:

```php
<?php

namespace App\CQRS\Handlers\User;

use App\Repositories\UserRepository;
use LaravelCQRS\Command;
use LaravelCQRS\Commands\User\CreateUserCommand;
use LaravelCQRS\Contracts\CommandHandlerInterface;

class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function handle(Command $command): \App\Models\User
    {
        /** @var CreateUserCommand $command */
        
        // Validate the command
        $validatedData = $command->validate();
        
        // Use validated data
        return $this->userRepository->create($validatedData);
    }
}
```

## Complete Example: Query with Validation

```php
<?php

namespace App\CQRS\Queries\User;

use LaravelCQRS\Query;

class GetUserQuery extends Query
{
    public function getId(): int|string|null
    {
        return $this->get('id');
    }

    /**
     * Validate that ID is provided and is numeric
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'User ID is required.',
            'id.integer' => 'User ID must be a valid number.',
        ];
    }
}
```

Usage in controller:

```php
public function show(Request $request, string $id): JsonResponse
{
    // Validate and dispatch in one call
    $user = \LaravelCQRS\CQRS::dispatchQuery(new GetUserQuery(['id' => $id]));

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    return response()->json($user);
}
```

## Available Methods

### `validate(): array`
Validates the command/query data and returns validated data. Throws `ValidationException` if validation fails.

```php
try {
    $validatedData = $command->validate();
} catch (\Illuminate\Validation\ValidationException $e) {
    // Handle validation errors
    return response()->json(['errors' => $e->errors()], 422);
}
```

### `isValid(): bool`
Checks if data is valid without throwing an exception.

```php
if ($command->isValid()) {
    // Proceed
} else {
    // Handle errors
}
```

### `errors(): MessageBag`
Gets validation errors without throwing an exception.

```php
$errors = $command->errors();
if ($errors->any()) {
    return response()->json(['errors' => $errors], 422);
}
```

## When to Use Each Method

- **`validate()`**: Use when you want Laravel to automatically handle validation errors (returns 422 response in API, redirects in web)
- **`isValid()` + `errors()`**: Use when you need custom error handling logic
- **Validate in Controller**: Best for API endpoints where you want immediate feedback
- **Validate in Handler**: Best when validation logic is complex or depends on business rules

## Best Practices

1. **Always validate user input** before processing commands/queries
2. **Use descriptive validation messages** to improve user experience
3. **Validate early** - prefer validating in controllers for better error responses
4. **Keep rules simple** - complex validation should be in form requests or handlers
5. **Reuse validation** - consider creating form requests for complex validation that's shared across multiple commands

## Integration with Form Requests

You can still use Laravel Form Requests for validation and pass validated data to commands:

```php
// In your controller
public function store(CreateUserRequest $request): JsonResponse
{
    // Form request already validated the data
    $command = new CreateUserCommand($request->validated());
    
    // Command validation is optional here, but can serve as a second layer
    $user = $this->commandBus->dispatch($command);

    return response()->json($user, 201);
}
```

This gives you:
- Form Request: HTTP-level validation (headers, file uploads, etc.)
- Command Validation: Business logic validation (data integrity, business rules)

