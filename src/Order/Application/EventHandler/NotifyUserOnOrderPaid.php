<?php

declare(strict_types=1);

namespace App\Order\Application\EventHandler;

use App\Order\Domain\Event\OrderPaid;
use App\Shared\Domain\NotificationServiceInterface;

/**
 * Application-layer handler for the OrderPaid domain event.
 *
 * Contains the business logic for reacting to a confirmed payment: notifying
 * the user. It depends only on the NotificationServiceInterface port, keeping
 * it completely decoupled from Symfony and any specific notification channel.
 *
 * This class is intentionally not a Symfony EventSubscriber. The framework
 * coupling is isolated in NotifyUserOnOrderPaidSubscriber (Infrastructure),
 * which calls this handler — making this class independently unit-testable.
 */
final readonly class NotifyUserOnOrderPaid
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {
    }

    public function handle(OrderPaid $event): void
    {
        $this->notificationService->notify(
            $event->userId,
            'Your order has been confirmed',
            \sprintf('Your order has been confirmed. Total: %.2f.', $event->totalPrice),
        );
    }
}
