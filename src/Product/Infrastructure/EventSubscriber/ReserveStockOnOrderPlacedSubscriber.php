<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\EventSubscriber;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Application\EventHandler\ReserveStockOnOrderPlaced;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Infrastructure adapter that connects Symfony's event dispatcher to the
 * application-layer ReserveStockOnOrderPlaced handler.
 *
 * The two-class split is intentional and a key pattern in this architecture:
 * - ReserveStockOnOrderPlaced (Application layer) holds the business logic and has
 *   no Symfony dependency — it can be unit-tested without booting a kernel.
 * - This subscriber (Infrastructure layer) is the Symfony-specific glue that
 *   listens for the event on the dispatcher and delegates to the handler.
 *
 * This way the "what to do" (business logic) stays separate from the
 * "how it is triggered" (framework wiring).
 */
final class ReserveStockOnOrderPlacedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ReserveStockOnOrderPlaced $handler)
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
