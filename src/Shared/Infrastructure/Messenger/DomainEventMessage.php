<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Domain\DomainEvent;

/**
 * Messenger envelope that carries a single domain event to the async transport.
 *
 * Keeping this in Infrastructure means the domain events themselves stay free of
 * any framework dependency. The publisher wraps them here before handing off to
 * Symfony Messenger.
 */
final readonly class DomainEventMessage
{
    public function __construct(public readonly DomainEvent $event) {}
}
