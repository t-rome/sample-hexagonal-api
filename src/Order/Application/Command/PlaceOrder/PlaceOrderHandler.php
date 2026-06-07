<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PlaceOrder;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Domain\DomainEventPublisherInterface;

final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function handle(PlaceOrderCommand $command): Order
    {
        $items = [];
        foreach ($command->items as $itemData) {
            $product = $this->productRepository->findById($itemData->productId);

            if (null === $product) {
                throw new ProductNotFoundException($itemData->productId);
            }

            $items[] = OrderItem::create(
                $product->getId() ?? throw new \LogicException('Product must have an id.'),
                $itemData->quantity,
                $product->getPrice(),
            );
        }

        $order = Order::place($command->userId, $items);
        $savedOrder = $this->orderRepository->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return $savedOrder;
    }
}
