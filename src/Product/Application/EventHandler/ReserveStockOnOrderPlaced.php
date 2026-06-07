<?php

declare(strict_types=1);

namespace App\Product\Application\EventHandler;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class ReserveStockOnOrderPlaced
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function handle(OrderPlaced $event): void
    {
        foreach ($event->items as $item) {
            $product = $this->productRepository->findById($item->getProductId())
                ?? throw new \LogicException(\sprintf('Product #%d not found during stock reservation.', $item->getProductId()));

            $product->reserveStock($item->getQuantity());
            $this->productRepository->save($product);
        }
    }
}
