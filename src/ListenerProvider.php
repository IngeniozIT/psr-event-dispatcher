<?php

declare(strict_types=1);

namespace IngeniozIT\EventDispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;
use IngeniozIT\EventDispatcher\InvalidArgumentException;

/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
class ListenerProvider implements ListenerProviderInterface
{
    protected $listeners = [];

    /**
     * Constructor.
     * @param callable[] $listeners Listeners to link to this provider.
     */
    public function __construct(iterable $listeners)
    {
        foreach ($listeners as $listener) {
            $this->addListener($listener);
        }
    }

    /**
     * Links a listener to the provider.
     * @param  callable $listener
     */
    protected function addListener($listener): void
    {
        $parameters = $this->getListenerParameters($listener);

        if (isset($parameters[1]) || !isset($parameters[0])) {
            throw new InvalidArgumentException('Listeners must have only one parameter.');
        }

        $type = $parameters[0]->hasType() ? (string)$parameters[0]->getType() : null;
        if ($type === 'object') {
            $type = null;
        }

        $this->listeners[] = [$listener, $type];
    }

    /**
     * Get the parameters of a listener.
     * @param  callable $listener
     * @return array
     */
    protected function getListenerParameters($listener): array
    {
        if (\is_array($listener)) {
            is_callable($listener, false, $name);
            $r = new \ReflectionMethod($name);
        } else {
            $r = new \ReflectionFunction($listener);
        }

        return $r->getParameters();
    }

    /**
     * @param object $event
     *   An event for which to return the relevant listeners.
     * @return callable[]
     *   An iterable (array, iterator, or generator) of callables.  Each
     *   callable MUST be type-compatible with $event.
     */
    public function getListenersForEvent(object $event): iterable
    {
        $suitableListeners = [];

        foreach ($this->listeners as list($listener, $type)) {
            if ($type !== null && !is_a($event, $type)) {
                continue;
            }
            $suitableListeners[] = $listener;
        }

        return $suitableListeners;
    }
}
