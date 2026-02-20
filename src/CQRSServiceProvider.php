<?php

namespace LaravelCQRS;

use Illuminate\Support\ServiceProvider;
use LaravelCQRS\Bus\CommandBus;
use LaravelCQRS\Bus\QueryBus;

class CQRSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register CommandBus as singleton
        $this->app->singleton(CommandBus::class, function ($app) {
            return new CommandBus($app);
        });

        // Register QueryBus as singleton
        $this->app->singleton(QueryBus::class, function ($app) {
            return new QueryBus($app);
        });

        // Create aliases for easier access
        $this->app->alias(CommandBus::class, 'cqrs.command-bus');
        $this->app->alias(QueryBus::class, 'cqrs.query-bus');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Enable automatic dependency injection for Command and Query classes
        // This allows type-hinting Command/Query classes in controller methods
        $this->app->resolving(function ($object, $app) {
            // Check if the object is a Command or Query instance
            if ($object instanceof Command || $object instanceof Query) {
                // Only inject request data if data is empty (not manually set)
                // This preserves backward compatibility with manual instantiation
                if (empty($object->getData()) && $app->bound('request')) {
                    try {
                        $request = $app->make('request');
                        $data = array_merge(
                            $request->all(),
                            $request->route() ? $request->route()->parameters() : []
                        );
                        
                        // Only set data if we have actual request data
                        if (!empty($data)) {
                            // Use reflection to set the data property
                            $reflection = new \ReflectionClass($object);
                            $property = $reflection->getProperty('data');
                            
                            // setAccessible() is only needed for PHP < 8.1
                            // In PHP 8.1+, reflection can access protected properties directly
                            if (PHP_VERSION_ID < 80100) {
                                $property->setAccessible(true);
                            }
                            
                            $property->setValue($object, $data);
                        }
                    } catch (\Exception $e) {
                        // If request is not available (e.g., in console), ignore
                    }
                }
            }
        });

        // Publish configuration file (optional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cqrs.php' => config_path('cqrs.php'),
            ], 'cqrs-config');

            // Register Artisan commands
            if ($this->app->runningInConsole()) {
                $this->commands([
                    \LaravelCQRS\Console\MakeCommandCommand::class,
                    \LaravelCQRS\Console\MakeQueryCommand::class,
                    \LaravelCQRS\Console\MakeHandlerCommand::class,
                    \LaravelCQRS\Console\MakeMiddlewareCommand::class,
                ]);
            }
        }
    }
}

