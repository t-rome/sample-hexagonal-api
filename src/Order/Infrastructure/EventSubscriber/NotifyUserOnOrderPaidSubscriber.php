<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\EventSubscriber;

use App\Order\Application\EventHandler\NotifyUserOnOrderPaid;
use App\Order\Domain\Event\OrderPaid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NotifyUserOnOrderPaidSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly NotifyUserOnOrderPaid $handler)
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
