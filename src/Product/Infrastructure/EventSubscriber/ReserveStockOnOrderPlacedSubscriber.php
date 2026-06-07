<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\EventSubscriber;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Application\EventHandler\ReserveStockOnOrderPlaced;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
