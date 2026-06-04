<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Event\OrderPaid;
use App\Order\Domain\Event\OrderPlaced;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Shared\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

class Order
{
    use AggregateRoot;

    /** @param OrderItem[] $items */
    private function __construct(
        // Domain identity: generated before persistence, so domain events carry a stable,
        // non-guessable ID without depending on a DB auto-increment.
        private readonly Uuid $uuid,
        // Surrogate key assigned by the DB; null until first persist.
        private readonly ?int $id,
        private readonly int $userId,
        private readonly array $items,
        private OrderStatus $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /** @param OrderItem[] $items */
    public static function place(int $userId, array $items): self
    {
        $uuid = new UuidV7();
        $createdAt = new \DateTimeImmutable();
        $order = new self($uuid, null, $userId, $items, OrderStatus::Pending, $createdAt);
        $order->recordEvent(new OrderPlaced($uuid, $userId, $order->getTotalPrice(), $createdAt, $items));

        return $order;
    }

    /** @param OrderItem[] $items */
    public static function reconstitute(
        Uuid $uuid,
        int $id,
        int $userId,
        array $items,
        OrderStatus $status,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($uuid, $id, $userId, $items, $status, $createdAt);
    }

    public function pay(): void
    {
        if (OrderStatus::Pending !== $this->status) {
            throw new OrderNotPayableException($this->id ?? 0, $this->status);
        }

        $this->status = OrderStatus::Confirmed;
        $this->recordEvent(new OrderPaid($this->uuid, $this->userId, $this->getTotalPrice(), new \DateTimeImmutable()));
    }

    public function getTotalPrice(): float
    {
        return array_sum(array_map(static fn (OrderItem $item) => $item->getTotalPrice(), $this->items));
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /** @return OrderItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
