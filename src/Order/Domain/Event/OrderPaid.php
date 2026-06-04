<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class OrderPaid implements DomainEvent
{
    public function __construct(
        public Uuid $orderUuid,
        public int $userId,
        public float $totalPrice,
        public \DateTimeImmutable $paidAt,
    ) {
    }
}
