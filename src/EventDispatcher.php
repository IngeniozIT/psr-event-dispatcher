<?php

declare(strict_types=1);

namespace IngeniozIT\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Defines a dispatcher for events.
 */
class EventDispatcher implements EventDispatcherInterface
{
    protected iterable $providers;

    /**
     * Constructor.
     *
     * @param ListenerProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event The object to process.
     * @return object The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event)
    {
        $isStoppable = is_a($event, StoppableEventInterface::class);

        foreach ($this->providers as $provider) {
            foreach ($provider->getListenersForEvent($event) as $listener) {
                if ($isStoppable && $event->isPropagationStopped()) {
                    break 2;
                }
                $listener($event);
            }
        }

        return $event;
    }
}
