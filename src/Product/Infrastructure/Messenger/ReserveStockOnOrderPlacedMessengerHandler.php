<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Messenger;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Application\EventHandler\ReserveStockOnOrderPlacedHandler;
use App\Shared\Infrastructure\Messenger\DomainEventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Messenger handler that delegates stock reservation to the application layer.
 *
 * This class is the async counterpart of ReserveStockOnOrderPlacedSubscriber
 * (the synchronous EventDispatcher subscriber). The application handler
 * ReserveStockOnOrderPlacedHandler is unchanged — only the trigger mechanism
 * moved from EventDispatcher (sync) to Messenger (async).
 *
 * Because all domain events travel as DomainEventMessage, multiple handlers
 * are registered for this message class. Each guards on its concrete event type
 * and returns early for events it does not own.
 */
#[AsMessageHandler]
final readonly class ReserveStockOnOrderPlacedMessengerHandler
{
    public function __construct(private readonly ReserveStockOnOrderPlacedHandler $handler) {}

    public function __invoke(DomainEventMessage $message): void
    {
        if (!$message->event instanceof OrderPlaced) {
            return;
        }

        $this->handler->handle($message->event);
    }
}
