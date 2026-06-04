<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain\Model;

use App\Order\Domain\Event\OrderPaid;
use App\Order\Domain\Event\OrderPlaced;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Model\OrderStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class OrderTest extends TestCase
{
    private function makeItems(): array
    {
        return [
            OrderItem::create(1, 2, 9.99),
            OrderItem::create(3, 1, 9.99),
        ];
    }

    public function testPlace(): void
    {
        $order = Order::place(42, $this->makeItems());

        $this->assertNull($order->getId());
        $this->assertInstanceOf(Uuid::class, $order->getUuid());
        $this->assertSame(42, $order->getUserId());
        $this->assertCount(2, $order->getItems());
        $this->assertSame(OrderStatus::Pending, $order->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
    }

    public function testPlaceComputesTotalPrice(): void
    {
        $order = Order::place(42, $this->makeItems());

        $this->assertEqualsWithDelta(29.97, $order->getTotalPrice(), 0.001);
    }

    public function testPlaceRecordsOrderPlacedEvent(): void
    {
        $order = Order::place(42, $this->makeItems());

        $events = $order->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertSame($order->getUuid(), $events[0]->orderUuid);
        $this->assertSame(42, $events[0]->userId);
        $this->assertEqualsWithDelta(29.97, $events[0]->totalPrice, 0.001);
    }

    public function testReleaseEventsClearsEvents(): void
    {
        $order = Order::place(42, $this->makeItems());
        $order->releaseEvents();

        $this->assertEmpty($order->releaseEvents());
    }

    public function testPayTransitionsToConfirmed(): void
    {
        $order = Order::reconstitute(Uuid::v7(), 1, 42, $this->makeItems(), OrderStatus::Pending, new \DateTimeImmutable());
        $order->releaseEvents();

        $order->pay();

        $this->assertSame(OrderStatus::Confirmed, $order->getStatus());
    }

    public function testPayRecordsOrderPaidEvent(): void
    {
        $order = Order::reconstitute(Uuid::v7(), 1, 42, $this->makeItems(), OrderStatus::Pending, new \DateTimeImmutable());
        $order->releaseEvents();

        $order->pay();
        $events = $order->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPaid::class, $events[0]);
        $this->assertSame(42, $events[0]->userId);
        $this->assertEqualsWithDelta(29.97, $events[0]->totalPrice, 0.001);
    }

    public function testPayThrowsWhenNotPending(): void
    {
        $order = Order::reconstitute(Uuid::v7(), 1, 42, $this->makeItems(), OrderStatus::Confirmed, new \DateTimeImmutable());

        $this->expectException(OrderNotPayableException::class);

        $order->pay();
    }

    public function testReconstitute(): void
    {
        $uuid = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-01-01');
        $items = $this->makeItems();

        $order = Order::reconstitute($uuid, 1, 42, $items, OrderStatus::Pending, $createdAt);

        $this->assertSame(1, $order->getId());
        $this->assertSame($uuid, $order->getUuid());
        $this->assertSame(42, $order->getUserId());
        $this->assertCount(2, $order->getItems());
        $this->assertSame(OrderStatus::Pending, $order->getStatus());
        $this->assertSame($createdAt, $order->getCreatedAt());
    }
}
