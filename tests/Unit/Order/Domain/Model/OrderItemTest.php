<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain\Model;

use App\Order\Domain\Model\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testCreate(): void
    {
        $item = OrderItem::create(1, 2, 9.99);

        $this->assertSame(1, $item->getProductId());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(9.99, $item->getUnitPrice());
    }

    public function testGetTotalPrice(): void
    {
        $item = OrderItem::create(1, 3, 9.99);

        $this->assertEqualsWithDelta(29.97, $item->getTotalPrice(), 0.001);
    }
}
