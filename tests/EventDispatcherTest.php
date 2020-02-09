<?php

declare(strict_types=1);

namespace IngeniozIT\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\EventDispatcher\EventDispatcher;
use Psr\EventDispatcher\ListenerProviderInterface;
use IngeniozIT\EventDispatcher\Tests\StoppableEventMock;

/**
 * A Dispatcher is a service object implementing EventDispatcherInterface.
 * It is responsible for retrieving Listeners from a Listener Provider for the
 * Event dispatched, and invoking each Listener with that Event.
 *
 * @coversDefaultClass \IngeniozIT\EventDispatcher\EventDispatcher
 */
class EventDispatcherTest extends TestCase
{
    protected array $eventsFired;

    public function setUp(): void
    {
        $this->eventsFired = [];
    }

    /**
     * Get a \Psr\EventDispatcher\ListenerProviderInterface mock.
     *
     * @param array $listeners Listeners the provider should return.
     * @return ListenerProviderInterface
     * @suppress PhanAccessMethodInternal
     * @suppress PhanTypeMismatchReturn
     */
    protected function getMockListenerProvider($listeners): ListenerProviderInterface
    {
        $mockListenerProvider = $this->createMock(ListenerProviderInterface::class);
        $mockListenerProvider
            ->method('getListenersForEvent')
            ->willReturn($listeners);

        return $mockListenerProvider;
    }

    /**
     * A Dispatcher MUST call Listeners synchronously in the order they are
     * returned from a ListenerProvider.
     * A Dispatcher MUST return the same Event object it was passed after it is
     * done invoking Listeners.
     * A Dispatcher MUST NOT return to the Emitter until all Listeners have
     * executed.
     */
    public function testDispatchesProvidersListeners(): void
    {
        $provider = $this->getMockListenerProvider([
            [$this, 'listenerOne'],
            [$this, 'listenerTwo'],
        ]);
        $event = new \stdClass();

        $dispatcher = new EventDispatcher([$provider]);

        $this->assertSame($event, $dispatcher->dispatch($event));
        $this->assertSame(['listenerOne', 'listenerTwo'], $this->eventsFired);
    }

    /**
     * An Exception or Error thrown by a Listener MUST block the execution of
     * any further Listeners. An Exception or Error thrown by a Listener MUST
     * be allowed to propagate back up to the Emitter.
     */
    public function testDoesNotBlockListenersExceptions(): void
    {
        $provider = $this->getMockListenerProvider([
            [$this, 'listenerOne'],
            [$this, 'exceptionListener'],
            [$this, 'listenerTwo'],
        ]);
        $event = new \stdClass();

        $dispatcher = new EventDispatcher([$provider]);

        $this->expectException(\Exception::class);
        try {
            $dispatcher->dispatch($event);
        } catch (\Exception $e) {
            $this->assertSame(['listenerOne'], $this->eventsFired);
            throw $e;
        }
    }

    public function listenerOne(object $event): void
    {
        $this->eventsFired[] = __FUNCTION__;
    }

    public function listenerTwo(object $event): void
    {
        $this->eventsFired[] = __FUNCTION__;
    }

    public function exceptionListener(object $event): void
    {
        throw new \Exception('That was expected.');
    }

    /**
     * If passed a Stoppable Event, a Dispatcher MUST call
     * isPropagationStopped() on the Event before each Listener has been
     * called.
     */
    public function testCallsIsPropagationStoppedWhenGivenAStoppableEvent(): void
    {
        $event = new StoppableEventMock();
        $provider = $this->getMockListenerProvider([
            [$event, 'listenerOne'],
            [$event, 'listenerTwo'],
        ]);
        $dispatcher = new EventDispatcher([$provider]);

        $dispatcher->dispatch($event);

        $this->assertSame([
            'isPropagationStopped',
            'listenerOne',
            'isPropagationStopped',
            'listenerTwo',
        ], $event->listenersCalled);
    }

    /**
     *  If that method returns true it MUST return the Event to the Emitter
     *  immediately and MUST NOT call any further Listeners.
     */
    public function testStopsPropagationWhenAStoppableEventReturnsTrue(): void
    {
        $event = new StoppableEventMock();
        $provider = $this->getMockListenerProvider([
            [$event, 'listenerOne'],
            [$event, 'listenerStopPropagation'],
            [$event, 'listenerTwo'],
        ]);
        $dispatcher = new EventDispatcher([$provider]);

        $dispatcher->dispatch($event);

        $this->assertSame([
            'isPropagationStopped',
            'listenerOne',
            'isPropagationStopped',
            'listenerStopPropagation',
            'isPropagationStopped',
        ], $event->listenersCalled);
    }
}
