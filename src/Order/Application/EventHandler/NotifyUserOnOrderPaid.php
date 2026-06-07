<?php

declare(strict_types=1);

namespace App\Order\Application\EventHandler;

use App\Order\Domain\Event\OrderPaid;
use App\Shared\Domain\NotificationServiceInterface;

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
