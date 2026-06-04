<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PlaceOrder;

final readonly class PlaceOrderCommand
{
    /** @param OrderItemData[] $items */
    public function __construct(
        public int $userId,
        public array $items,
    ) {
    }
}
