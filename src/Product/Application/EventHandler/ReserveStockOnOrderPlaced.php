<?php

declare(strict_types=1);

namespace App\Product\Application\EventHandler;

use App\Order\Domain\Event\OrderPlaced;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ReserveStockOnOrderPlaced implements EventSubscriberInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [OrderPlaced::class => 'onOrderPlaced'];
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        foreach ($event->items as $item) {
            $product = $this->productRepository->findById($item->getProductId())
                ?? throw new \LogicException(\sprintf('Product #%d not found during stock reservation.', $item->getProductId()));

            $product->reserveStock($item->getQuantity());
            $this->productRepository->save($product);
        }
    }
}
