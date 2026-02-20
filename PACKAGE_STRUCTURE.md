# Package Structure

```
laravel-cqrs-package/
├── src/
│   ├── Command.php                    # Base Command class
│   ├── Query.php                      # Base Query class
│   ├── CQRS.php                      # CQRS helper class (recommended)
│   ├── Pipeline.php                  # Middleware pipeline executor
│   ├── Bus/
│   │   ├── AbstractBus.php           # Base bus class
│   │   ├── CommandBus.php            # Command dispatcher
│   │   └── QueryBus.php              # Query dispatcher
│   ├── Contracts/
│   │   ├── CommandHandlerInterface.php
│   │   ├── QueryHandlerInterface.php
│   │   └── MiddlewareInterface.php   # Middleware interface
│   ├── Exceptions/
│   │   ├── HandlerNotFoundException.php
│   │   └── InvalidHandlerException.php
│   └── CQRSServiceProvider.php       # Laravel service provider
├── config/
│   └── cqrs.php                      # Configuration file
├── composer.json                     # Package definition
├── README.md                         # Main documentation
├── INSTALLATION.md                   # Installation guide
├── VALIDATION_USAGE.md              # Validation guide
├── MIDDLEWARE_USAGE.md              # Middleware guide
├── EXAMPLES.md                       # Usage examples
├── LICENSE                           # MIT License
└── CHANGELOG.md                      # Version history
```

## Namespace

All classes use the `LaravelCQRS` namespace:

- `LaravelCQRS\Command` - Base command class
- `LaravelCQRS\Query` - Base query class
- `LaravelCQRS\CQRS` - Helper class for dispatching (recommended)
- `LaravelCQRS\Pipeline` - Middleware pipeline executor
- `LaravelCQRS\Bus\AbstractBus` - Base bus class
- `LaravelCQRS\Bus\CommandBus` - Command dispatcher
- `LaravelCQRS\Bus\QueryBus` - Query dispatcher
- `LaravelCQRS\Contracts\CommandHandlerInterface` - Command handler interface
- `LaravelCQRS\Contracts\QueryHandlerInterface` - Query handler interface
- `LaravelCQRS\Contracts\MiddlewareInterface` - Middleware interface
- `LaravelCQRS\Exceptions\HandlerNotFoundException` - Handler not found exception
- `LaravelCQRS\Exceptions\InvalidHandlerException` - Invalid handler exception

## Usage in Your Project

After installation, your project structure should be:

```
app/
├── CQRS/
│   ├── Commands/
│   │   └── User/
│   │       └── CreateUserCommand.php
│   ├── Queries/
│   │   └── User/
│   │       └── GetUserQuery.php
│   └── Handlers/
│       └── User/
│           ├── CreateUserCommandHandler.php
│           └── GetUserQueryHandler.php
```

Your commands/queries extend `LaravelCQRS\Command` and `LaravelCQRS\Query`.

Your handlers implement `LaravelCQRS\Contracts\CommandHandlerInterface` and `LaravelCQRS\Contracts\QueryHandlerInterface`.

