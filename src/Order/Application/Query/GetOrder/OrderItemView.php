<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrder;

use App\Order\Domain\Model\OrderItem;

final readonly class OrderItemView
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
    ) {
    }

    public static function fromDomain(OrderItem $item): self
    {
        return new self(
            productId: $item->getProductId(),
            quantity: $item->getQuantity(),
            unitPrice: $item->getUnitPrice(),
            totalPrice: $item->getTotalPrice(),
        );
    }
}
