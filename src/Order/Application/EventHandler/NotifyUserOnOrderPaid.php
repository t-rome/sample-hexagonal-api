<?php

declare(strict_types=1);

namespace App\Order\Application\EventHandler;

use App\Order\Domain\Event\OrderPaid;
use App\Shared\Domain\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class NotifyUserOnOrderPaid implements EventSubscriberInterface
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [OrderPaid::class => 'onOrderPaid'];
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $this->notificationService->notify(
            $event->userId,
            'Your order has been confirmed',
            \sprintf('Your order has been confirmed. Total: %.2f.', $event->totalPrice),
        );
    }
}
