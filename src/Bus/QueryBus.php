<?php

namespace LaravelCQRS\Bus;

use Illuminate\Contracts\Container\Container;
use LaravelCQRS\Contracts\QueryHandlerInterface;
use LaravelCQRS\Query;

class QueryBus extends AbstractBus
{
    /**
     * Dispatch a query.
     *
     * @param Query $query
     * @return mixed
     */
    public function dispatch(Query $query): mixed
    {
        /** @var QueryHandlerInterface $handler */
        $handler = $this->resolveHandler($query, QueryHandlerInterface::class);

        return $this->executeThroughPipeline($query, $handler, 'handle');
    }
}

