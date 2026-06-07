<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Marker interface for domain events.
 *
 * A domain event represents something meaningful that happened in the business
 * domain (e.g. "an order was placed", "a payment succeeded"). Events describe
 * a fact in the past — they carry data about what happened, not instructions
 * about what should happen next.
 *
 * Other modules react to events without the emitting module knowing anything
 * about them, which keeps modules decoupled. The reaction logic lives in
 * Application-layer EventHandlers, wired up by Infrastructure EventSubscribers.
 */
interface DomainEvent
{
}
