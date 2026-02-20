<?php

namespace LaravelCQRS\Contracts;

use LaravelCQRS\Query;

interface QueryHandlerInterface
{
    /**
     * Handle the query.
     *
     * @param Query $query
     * @return mixed
     */
    public function handle(Query $query): mixed;
}

