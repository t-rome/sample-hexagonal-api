<?php

declare(strict_types=1);

namespace App\Order\Application\Query\ReadModel;

use App\Order\Domain\Model\Order;

/**
 * Read model (DTO) returned by order query handlers.
 *
 * Query handlers return a view object instead of the domain model for two reasons:
 * 1. Decoupling: the API response shape is independent of domain internals — the
 *    domain can evolve without breaking the read API, and vice versa.
 * 2. Encapsulation: domain logic and invariants are not exposed to the presentation
 *    layer; consumers get a flat, serialization-friendly structure.
 *
 * fromDomain() is the only place that knows how to map from a domain Order to this
 * flat structure. All fields are strings or scalar types — no domain objects leak out.
 */
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
