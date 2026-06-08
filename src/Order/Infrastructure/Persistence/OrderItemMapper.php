<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Domain\Model\OrderItem;

/**
 * Translates between the OrderItem domain object and its Doctrine ORM record.
 *
 * Used internally by OrderMapper — never called directly from outside the
 * persistence layer. See OrderMapper for the full mapping rationale.
 */
final readonly class OrderItemMapper
{
    public function toDomain(OrderItemRecord $record): OrderItem
    {
        return OrderItem::create(
            productId: $record->productId,
            quantity: $record->quantity,
            unitPrice: $record->unitPrice,
        );
    }

    public function toRecord(OrderItem $item, OrderRecord $orderRecord): OrderItemRecord
    {
        $record = new OrderItemRecord();
        $record->order = $orderRecord;
        $record->productId = $item->getProductId();
        $record->quantity = $item->getQuantity();
        $record->unitPrice = $item->getUnitPrice();

        return $record;
    }
}
