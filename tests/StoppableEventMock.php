<?php

declare(strict_types=1);

namespace IngeniozIT\EventDispatcher\Tests;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Mock of a stoppable event with its related listeners.
 */
class StoppableEventMock implements StoppableEventInterface
{
    public array $listenersCalled = [];

    protected bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        $this->listenersCalled[] = __FUNCTION__;
        return $this->propagationStopped;
    }

    public function listenerOne(object $event): void
    {
        $this->listenersCalled[] = __FUNCTION__;
    }

    public function listenerTwo(object $event): void
    {
        $this->listenersCalled[] = __FUNCTION__;
    }

    public function listenerStopPropagation(object $event): void
    {
        $this->listenersCalled[] = __FUNCTION__;
        $this->propagationStopped = true;
    }
}
