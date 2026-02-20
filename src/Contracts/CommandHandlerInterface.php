<?php

namespace LaravelCQRS\Contracts;

use LaravelCQRS\Command;

interface CommandHandlerInterface
{
    /**
     * Handle the command.
     *
     * @param Command $command
     * @return mixed
     */
    public function handle(Command $command): mixed;
}

