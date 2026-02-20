<?php

namespace LaravelCQRS\Bus;

use Illuminate\Contracts\Container\Container;
use LaravelCQRS\Command;
use LaravelCQRS\Contracts\CommandHandlerInterface;

class CommandBus extends AbstractBus
{
    /**
     * Dispatch a command.
     *
     * @param Command $command
     * @return mixed
     */
    public function dispatch(Command $command): mixed
    {
        /** @var CommandHandlerInterface $handler */
        $handler = $this->resolveHandler($command, CommandHandlerInterface::class);

        return $this->executeThroughPipeline($command, $handler, 'handle');
    }
}

