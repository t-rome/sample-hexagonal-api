<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\EventSubscriber;

use App\Order\Application\EventHandler\NotifyUserOnOrderPaidHandler;
use App\Order\Domain\Event\OrderPaid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SYNCHRONOUS adapter — connects Symfony's EventDispatcher to the application
 * layer handler NotifyUserOnOrderPaidHandler.
 *
 * This subscriber is only active when DomainEventPublisherInterface is wired to
 * SymfonyDomainEventPublisher (see config/services.yaml). When the async adapter
 * MessengerDomainEventPublisher is active instead, domain events never reach the
 * EventDispatcher and this class is never called — its async counterpart
 * NotifyUserOnOrderPaidMessengerHandler takes over.
 *
 * --- When to prefer the synchronous approach (EventDispatcher) ---
 *
 * ✔ Simple applications or early-stage projects where operational overhead matters.
 * ✔ The side-effect MUST succeed or the whole request should fail (strong consistency):
 *   e.g. reserving stock and placing the order are one atomic unit of work.
 * ✔ No message broker available in the target environment.
 * ✔ Debugging is easier: the full call stack is visible in a single request trace.
 * ✔ No risk of messages being processed more than once (no at-least-once delivery).
 *
 * --- When to prefer the asynchronous approach (Messenger) ---
 *
 * ✔ The side-effect is a non-critical background task (email, notification, invoice).
 * ✔ The HTTP response should not block on slow operations (PDF generation, 3rd-party APIs).
 * ✔ Retry semantics are needed: if the notification service is temporarily down,
 *   the message stays in the queue and is retried automatically.
 * ✔ Cross-service or cross-deployment communication (microservices, event streaming).
 * ✔ High-throughput scenarios where handler latency would degrade request times.
 *
 * --- Two-class split ---
 *
 * In either approach the application handler (NotifyUserOnOrderPaidHandler) is
 * identical — it contains the business logic and has no framework dependency.
 * Only the triggering mechanism differs: this subscriber vs. the Messenger handler.
 */
final readonly class NotifyUserOnOrderPaidSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly NotifyUserOnOrderPaidHandler $handler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [OrderPaid::class => 'onOrderPaid'];
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $this->handler->handle($event);
    }
}
