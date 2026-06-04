<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PlaceOrder;

final readonly class OrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }
}
