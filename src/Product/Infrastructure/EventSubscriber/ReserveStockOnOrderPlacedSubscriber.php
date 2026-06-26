<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\EventSubscriber;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Application\EventHandler\ReserveStockOnOrderPlacedHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SYNCHRONOUS adapter — connects Symfony's EventDispatcher to the application
 * layer handler ReserveStockOnOrderPlacedHandler.
 *
 * This subscriber is only active when DomainEventPublisherInterface is wired to
 * SymfonyDomainEventPublisher (see config/services.yaml). When the async adapter
 * MessengerDomainEventPublisher is active instead, domain events never reach the
 * EventDispatcher and this class is never called — its async counterpart
 * ReserveStockOnOrderPlacedMessengerHandler takes over.
 *
 * --- When to prefer the synchronous approach (EventDispatcher) ---
 *
 * ✔ Strong consistency is required: stock reservation and order placement should
 *   either both succeed or both fail. With a synchronous handler, an
 *   InsufficientStockException rolls back the entire request before the order
 *   is persisted — the domain invariant is enforced in the same transaction.
 * ✔ Simple deployment: no broker, no worker process, no at-least-once delivery
 *   concerns.
 * ✔ Straightforward debugging: exceptions propagate directly up the call stack
 *   and are visible in the same request trace.
 *
 * --- When to prefer the asynchronous approach (Messenger) ---
 *
 * ✔ Eventual consistency is acceptable: the order is confirmed immediately and
 *   stock is decremented moments later by the worker. Useful when the Product
 *   BC lives in a separate service or database.
 * ✔ Resilience: if the stock service is temporarily unavailable the message stays
 *   in the queue and is retried automatically — no order is silently lost.
 * ✔ High-throughput: stock updates for many concurrent orders can be processed
 *   sequentially by the worker, avoiding DB contention on the product row.
 *
 * NOTE — consistency trade-off: with async stock reservation it is theoretically
 * possible to place more orders than there is stock if many arrive simultaneously
 * before the worker runs. In a real system this is handled by idempotent handlers,
 * optimistic locking, or a compensating transaction (saga pattern).
 *
 * --- Two-class split ---
 *
 * In either approach the application handler (ReserveStockOnOrderPlacedHandler) is
 * identical — it contains the business logic and has no framework dependency.
 * Only the triggering mechanism differs: this subscriber vs. the Messenger handler.
 */
final readonly class ReserveStockOnOrderPlacedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ReserveStockOnOrderPlacedHandler $handler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [OrderPlaced::class => 'onOrderPlaced'];
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->handler->handle($event);
    }
}
