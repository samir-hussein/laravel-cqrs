<?php

namespace LaravelCQRS;

use Closure;
use LaravelCQRS\Contracts\MiddlewareInterface;

/**
 * Pipeline Class
 * 
 * Executes a stack of middleware around handler execution.
 */
class Pipeline
{
    /**
     * The middleware stack.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The final handler closure.
     *
     * @var Closure|null
     */
    protected ?Closure $handler = null;

    /**
     * Create a new pipeline instance.
     *
     * @param array $middleware
     * @param Closure $handler
     */
    public function __construct(array $middleware, Closure $handler)
    {
        $this->middleware = $middleware;
        $this->handler = $handler;
    }

    /**
     * Execute the pipeline.
     *
     * @param Command|Query $commandOrQuery
     * @return mixed
     */
    public function execute(Command|Query $commandOrQuery): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $this->prepareHandler()
        );

        return $pipeline($commandOrQuery);
    }

    /**
     * Get a Closure that carries the middleware execution.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $middleware) {
            return function ($commandOrQuery) use ($stack, $middleware) {
                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->handle($commandOrQuery, $stack);
                }

                if (is_string($middleware)) {
                    $middleware = \app($middleware);
                }

                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->handle($commandOrQuery, $stack);
                }

                throw new \RuntimeException(
                    'Middleware must implement ' . MiddlewareInterface::class
                );
            };
        };
    }

    /**
     * Prepare the final handler closure.
     *
     * @return Closure
     */
    protected function prepareHandler(): Closure
    {
        return function ($commandOrQuery) {
            return ($this->handler)($commandOrQuery);
        };
    }
}

