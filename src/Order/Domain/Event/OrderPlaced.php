<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\Model\OrderItem;
use App\Shared\Domain\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class OrderPlaced implements DomainEvent
{
    /** @param OrderItem[] $items */
    public function __construct(
        public Uuid $orderUuid,
        public int $userId,
        public float $totalPrice,
        public \DateTimeImmutable $placedAt,
        public array $items,
    ) {
    }
}
