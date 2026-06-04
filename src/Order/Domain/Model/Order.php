<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Event\OrderPlaced;
use App\Shared\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

class Order
{
    use AggregateRoot;

    public const STATUS_PENDING = 'pending';

    /** @param OrderItem[] $items */
    private function __construct(
        private readonly Uuid $uuid,
        private readonly ?int $id,
        private readonly int $userId,
        private readonly array $items,
        private readonly string $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /** @param OrderItem[] $items */
    public static function place(int $userId, array $items): self
    {
        $uuid = new UuidV7();
        $createdAt = new \DateTimeImmutable();
        $order = new self($uuid, null, $userId, $items, self::STATUS_PENDING, $createdAt);
        $order->recordEvent(new OrderPlaced($uuid, $userId, $order->getTotalPrice(), $createdAt, $items));

        return $order;
    }

    /** @param OrderItem[] $items */
    public static function reconstitute(
        Uuid $uuid,
        int $id,
        int $userId,
        array $items,
        string $status,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($uuid, $id, $userId, $items, $status, $createdAt);
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
