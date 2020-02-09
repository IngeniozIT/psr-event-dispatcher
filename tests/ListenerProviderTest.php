<?php

declare(strict_types=1);

namespace IngeniozIT\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\EventDispatcher\ListenerProvider;
use IngeniozIT\EventDispatcher\InvalidArgumentException;

/**
 * A Listener Provider is a service object responsible for determining what
 * Listeners are relevant to and should be called for a given Event. It may
 * determine both what Listeners are relevant and the order in which to return
 * them by whatever means it chooses.
 *
 * @coversDefaultClass \IngeniozIT\EventDispatcher\ListenerProvider
 */
class ListenerProviderTest extends TestCase
{
    public function testProvidesNoListenersWhenEmpty(): void
    {
        $provider = new ListenerProvider([]);
        $event = new \stdClass();

        $this->assertEmpty($provider->getListenersForEvent($event));
    }

    /**
     * Allowing for some form of registration mechanism so that implementers
     * may assign a Listener to an Event in a fixed order.
     * Generating a compiled list of Listeners ahead of time that may be
     * consulted at runtime.
     */
    public function testProvidesArrayListeners(): void
    {
        $listeners = [
            [$this, 'listener'],
            [get_class($this), 'staticListener'],
        ];
        $provider = new ListenerProvider($listeners);
        $event = new \stdClass();

        $this->assertEquals($listeners, $provider->getListenersForEvent($event));
    }

    public function testProvidesClosuresListeners(): void
    {
        $listeners = [
            function ($a) {
            }
        ];
        $provider = new ListenerProvider($listeners);
        $event = new \stdClass();

        $this->assertEquals($listeners, $provider->getListenersForEvent($event));
    }

    public function testProvidesFunctionListeners(): void
    {
        $listeners = ['\IngeniozIT\EventDispatcher\Tests\listener'];
        $provider = new ListenerProvider($listeners);
        $event = new \stdClass();

        $this->assertEquals($listeners, $provider->getListenersForEvent($event));
    }

    /**
     * Deriving a list of applicable Listeners through reflection based on the
     * type and implemented interfaces of the Event.
     */
    public function testDoesNotSelectListenersWithWrongEventType(): void
    {
        $listeners = [
            [$this, 'listener'],
            [$this, 'nonMatchingListener'],
            [$this, 'matchingListener'],
        ];
        $matchingListeners = [$listeners[0], $listeners[2]];
        $provider = new ListenerProvider($listeners);
        $event = new \stdClass();

        $this->assertEquals($matchingListeners, $provider->getListenersForEvent($event));
    }

    public function testThrowsExceptionIfAListenerHasNoParameter(): void
    {
        $listeners = [
            [$this, 'listenerWithNoParameters'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $provider = new ListenerProvider($listeners);
    }

    public function testThrowsExceptionIfAListenerHasMoreThanOneParameter(): void
    {
        $listeners = [
            [$this, 'listenerWithMultipleParameters'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $provider = new ListenerProvider($listeners);
    }

    public function listener($event): void
    {
    }

    public static function staticListener(object $event): void
    {
    }

    public function nonMatchingListener(TestCase $event): void
    {
    }

    public function matchingListener(\stdClass $event): void
    {
    }

    public function listenerWithMultipleParameters(\stdClass $event, $badParameter): void
    {
    }

    public function listenerWithNoParameters(): void
    {
    }
}

function listener(object $event): void
{
}
