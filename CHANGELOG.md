# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - TBD

### Added
- **Initial Release** - Complete CQRS pattern implementation for Laravel
- **Command and Query Base Classes** - `LaravelCQRS\Command` and `LaravelCQRS\Query` with data access methods
- **CommandBus and QueryBus** - Dispatchers for commands and queries with automatic handler resolution
- **CQRS Helper Class** (`LaravelCQRS\CQRS`) - Universal dispatch method that automatically detects Command or Query
- **Handler Interfaces** - `CommandHandlerInterface` and `QueryHandlerInterface` for type-safe handlers
- **Auto-resolution** - Handlers are automatically resolved based on naming conventions
- **AbstractBus** - Base bus class to reduce code duplication and improve maintainability
- **Middleware Support** - Pipeline system for wrapping handlers with middleware (logging, transactions, authorization, etc.)
  - `MiddlewareInterface` for creating custom middleware
  - Global and command/query-specific middleware configuration
  - Pipeline execution system
- **Validation Support** - Built-in validation methods for commands and queries
  - `rules()`, `messages()`, `attributes()` methods for defining validation
  - `validate()`, `isValid()`, `errors()` methods for validation execution
- **Custom Exceptions** - Better error handling with specific exception classes
  - `HandlerNotFoundException` - When handler class is not found
  - `InvalidHandlerException` - When handler doesn't implement required interface
- **Configuration System** - Flexible configuration via `config/cqrs.php`
  - Handler namespace configuration
  - Command and query namespace configuration
  - Auto-resolution toggle
  - Handler mappings for custom handler locations
  - Middleware configuration (global and specific)
- **Laravel Integration** - Seamless integration with Laravel's service container
  - Service provider auto-discovery
  - Singleton bus registration
  - Container-based dependency injection
- **Comprehensive Documentation**
  - README.md with quick start guide
  - VALIDATION_USAGE.md - Complete validation guide
  - MIDDLEWARE_USAGE.md - Middleware implementation guide
  - EXAMPLES.md - Real-world usage examples
  - INSTALLATION.md - Installation instructions
  - PACKAGE_STRUCTURE.md - Package architecture overview

### Features
- Type-safe with full PHP 8.2+ type hints
- Works with any Laravel project structure
- Flexible handler resolution (convention-based or configuration-based)
- Automatic validation support
- Middleware pipeline for cross-cutting concerns
- Clean and simple API

