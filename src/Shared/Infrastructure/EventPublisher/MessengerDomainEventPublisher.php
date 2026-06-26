<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventPublisher;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\DomainEventPublisherInterface;
use App\Shared\Infrastructure\Messenger\DomainEventMessage;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * ASYNCHRONOUS adapter for DomainEventPublisherInterface.
 *
 * Wraps domain events in a DomainEventMessage envelope and hands them off to
 * Symfony Messenger. The transport (RabbitMQ/AMQP, Redis, Doctrine, sync) is
 * configured in config/packages/messenger.yaml via MESSENGER_TRANSPORT_DSN —
 * zero PHP changes required to switch brokers.
 *
 * Activate this adapter in config/services.yaml:
 *
 *     App\Shared\Domain\DomainEventPublisherInterface:
 *         class: App\Shared\Infrastructure\EventPublisher\MessengerDomainEventPublisher
 *
 * --- Characteristics ---
 *
 * ✔ Non-blocking: the HTTP response is returned before handlers run.
 * ✔ Resilience: transient failures are retried automatically by the worker.
 * ✔ Scalability: worker processes can run in parallel and be scaled independently.
 * ✔ Transport is a config value: switch broker without touching any PHP code.
 * ✗ Eventual consistency: side-effects (stock, notifications) happen moments later,
 *   not within the same DB transaction as the command.
 * ✗ Operational overhead: requires a running broker and at least one worker process.
 * ✗ At-least-once delivery: handlers must be idempotent (safe to call more than once).
 *
 * Publishing flow:
 *   CommandHandler → DomainEventPublisherInterface::publish(event)   ← this class
 *     → Messenger::dispatch(DomainEventMessage(event))
 *     → AMQP transport → RabbitMQ queue
 *     → Worker: messenger:consume domain_events
 *     → Infrastructure MessengerHandler (e.g. NotifyUserOnOrderPaidMessengerHandler)
 *     → Application-layer EventHandler (pure PHP, no framework imports)
 *
 * @see SymfonyDomainEventPublisher for the synchronous alternative
 */
final readonly class MessengerDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function publish(DomainEvent $event): void
    {
        $this->messageBus->dispatch(new DomainEventMessage($event));
    }
}
