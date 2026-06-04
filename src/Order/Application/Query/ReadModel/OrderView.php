<?php

declare(strict_types=1);

namespace App\Order\Application\Query\ReadModel;

use App\Order\Domain\Model\Order;

final readonly class OrderView
{
    /** @param OrderItemView[] $items */
    public function __construct(
        public int $id,
        public string $uuid,
        public int $userId,
        public array $items,
        public float $totalPrice,
        public string $status,
        public string $createdAt,
    ) {
    }

    public static function fromDomain(Order $order): self
    {
        return new self(
            id: $order->getId() ?? throw new \LogicException('Order must have an id to create a view.'),
            uuid: (string) $order->getUuid(),
            userId: $order->getUserId(),
            items: array_map(OrderItemView::fromDomain(...), $order->getItems()),
            totalPrice: $order->getTotalPrice(),
            status: $order->getStatus()->value,
            createdAt: $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
