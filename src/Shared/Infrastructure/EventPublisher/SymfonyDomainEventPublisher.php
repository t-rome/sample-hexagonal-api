<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventPublisher;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * SYNCHRONOUS adapter for DomainEventPublisherInterface.
 *
 * Forwards domain events directly to Symfony's EventDispatcher. Registered
 * EventSubscribers (e.g. NotifyUserOnOrderPaidSubscriber) are called inline,
 * within the same request and the same database transaction.
 *
 * Activate this adapter in config/services.yaml:
 *
 *     App\Shared\Domain\DomainEventPublisherInterface:
 *         class: App\Shared\Infrastructure\EventPublisher\SymfonyDomainEventPublisher
 *
 * --- Characteristics ---
 *
 * ✔ Zero infrastructure: no broker, no worker process needed.
 * ✔ Strong consistency: if a subscriber throws, the exception propagates and the
 *   HTTP request fails — order and side-effect either both succeed or both fail.
 * ✔ Simple observability: exceptions and traces are visible in a single request.
 * ✗ Latency: slow subscribers (email, PDF) block the HTTP response.
 * ✗ No retry: a transient failure in a subscriber aborts the whole request.
 * ✗ Tight temporal coupling: subscriber must be healthy at the moment of the call.
 *
 * Publishing flow:
 *   CommandHandler → DomainEventPublisherInterface::publish(event)   ← this class
 *     → Symfony EventDispatcher::dispatch(event)
 *     → EventSubscriberInterface implementations (Infrastructure)
 *     → Application-layer EventHandlers (pure PHP, no framework imports)
 *
 * @see MessengerDomainEventPublisher for the async alternative
 */
final readonly class SymfonyDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function publish(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
