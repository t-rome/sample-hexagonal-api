<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventPublisher;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Adapter that bridges domain events to Symfony's event dispatcher.
 *
 * Domain events implement only the DomainEvent marker interface (no Symfony
 * dependency). Symfony's dispatcher accepts any object as an event, so no
 * framework coupling leaks into the domain layer.
 *
 * Publishing flow:
 *   CommandHandler saves Aggregate → repository calls releaseEvents()
 *     → DomainEventPublisherInterface::publish(event) — this class
 *     → Symfony EventDispatcher::dispatch(event)
 *     → Infrastructure EventSubscribers are invoked
 *     → they delegate to Application-layer EventHandlers
 */
final class SymfonyDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function publish(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
