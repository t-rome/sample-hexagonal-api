<?php

declare(strict_types=1);

namespace App\Product\Application\EventHandler;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Domain\Repository\ProductRepositoryInterface;

/**
 * Application-layer handler for the OrderPlaced domain event.
 *
 * This is an example of cross-bounded-context communication via domain events:
 * the Order BC publishes OrderPlaced; the Product BC reacts by reserving stock —
 * without either module having a direct dependency on the other.
 *
 * For each item in the order, the corresponding Product is loaded, its stock is
 * decreased via the domain method reserveStock(), and the updated Product is saved.
 * If a product no longer exists at this point it is a data-integrity error
 * (LogicException), not a user-facing validation error.
 *
 * This class is intentionally not a Symfony EventSubscriber. The framework
 * coupling is isolated in ReserveStockOnOrderPlacedSubscriber (Infrastructure),
 * which calls this handler — making this class independently unit-testable.
 */
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
