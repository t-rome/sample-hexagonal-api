<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Messenger;

use App\Order\Application\EventHandler\NotifyUserOnOrderPaidHandler;
use App\Order\Domain\Event\OrderPaid;
use App\Shared\Infrastructure\Messenger\DomainEventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Messenger handler that delegates payment notification to the application layer.
 *
 * This class is the async counterpart of NotifyUserOnOrderPaidSubscriber
 * (the synchronous EventDispatcher subscriber). The application handler
 * NotifyUserOnOrderPaidHandler is unchanged — only the trigger mechanism
 * moved from EventDispatcher (sync) to Messenger (async).
 *
 * Because all domain events travel as DomainEventMessage, multiple handlers
 * are registered for this message class. Each guards on its concrete event type
 * and returns early for events it does not own.
 */
#[AsMessageHandler]
final readonly class NotifyUserOnOrderPaidMessengerHandler
{
    public function __construct(private readonly NotifyUserOnOrderPaidHandler $handler) {}

    public function __invoke(DomainEventMessage $message): void
    {
        if (!$message->event instanceof OrderPaid) {
            return;
        }

        $this->handler->handle($message->event);
    }
}
