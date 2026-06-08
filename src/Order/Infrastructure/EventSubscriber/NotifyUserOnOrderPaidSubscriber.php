<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\EventSubscriber;

use App\Order\Application\EventHandler\NotifyUserOnOrderPaidHandler;
use App\Order\Domain\Event\OrderPaid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Infrastructure adapter that connects Symfony's event dispatcher to the
 * application-layer NotifyUserOnOrderPaidHandler.
 *
 * The two-class split is intentional and a key pattern in this architecture:
 * - NotifyUserOnOrderPaidHandler (Application layer) holds the business logic and has
 *   no Symfony dependency — it can be unit-tested without booting a kernel.
 * - This subscriber (Infrastructure layer) is the Symfony-specific glue that
 *   listens for the event on the dispatcher and delegates to the handler.
 *
 * This way the "what to do" (business logic) stays separate from the
 * "how it is triggered" (framework wiring).
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
