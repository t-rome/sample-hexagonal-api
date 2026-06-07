<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Port for dispatching domain events out of the domain layer.
 *
 * Defined here in Domain so that application handlers can publish events without
 * depending on any framework. The concrete adapter (SymfonyDomainEventPublisher
 * in Infrastructure) wires this to Symfony's event dispatcher.
 *
 * This follows the Dependency Inversion Principle: the domain defines what it
 * needs; Infrastructure provides the concrete mechanism.
 */
interface DomainEventPublisherInterface
{
    public function publish(DomainEvent $event): void;
}
