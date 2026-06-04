<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Domain\Model\Order;

final class OrderMapper
{
    public function __construct(private readonly OrderItemMapper $itemMapper)
    {
    }

    public function toDomain(OrderRecord $record): Order
    {
        return Order::reconstitute(
            uuid: $record->uuid,
            id: $record->id ?? throw new \LogicException('OrderRecord must have an id.'),
            userId: $record->userId,
            items: array_map($this->itemMapper->toDomain(...), $record->items->toArray()),
            status: $record->status,
            createdAt: $record->createdAt,
        );
    }

    public function toRecord(Order $order, ?OrderRecord $existing = null): OrderRecord
    {
        $record = $existing ?? new OrderRecord();
        $record->uuid = $order->getUuid();
        $record->userId = $order->getUserId();
        $record->status = $order->getStatus();
        $record->createdAt = $order->getCreatedAt();

        $record->items->clear();
        foreach ($order->getItems() as $item) {
            $record->items->add($this->itemMapper->toRecord($item, $record));
        }

        return $record;
    }
}
