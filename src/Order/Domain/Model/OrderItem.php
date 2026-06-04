<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

final class OrderItem
{
    private function __construct(
        private readonly int $productId,
        private readonly int $quantity,
        private readonly float $unitPrice,
    ) {
    }

    public static function create(int $productId, int $quantity, float $unitPrice): self
    {
        return new self($productId, $quantity, $unitPrice);
    }

    public function getTotalPrice(): float
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }
}
