<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Mixin for the root entity of a DDD Aggregate.
 *
 * An Aggregate is a cluster of domain objects (e.g. Order + OrderItems) that
 * must always be consistent with each other. The Aggregate Root is the single
 * entry point: external code may only hold a reference to the root, never to
 * inner objects directly.
 *
 * Domain events produced during a business operation are collected here via
 * recordEvent() and released by the repository after the state change is safely
 * persisted — ensuring events are dispatched only once the write succeeds.
 */
trait AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /** Call this inside domain methods whenever something meaningful happens (e.g. an order is placed). */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Returns all recorded events and clears the internal list.
     * Called by the repository after saving so events are published exactly once.
     *
     * @return DomainEvent[]
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
