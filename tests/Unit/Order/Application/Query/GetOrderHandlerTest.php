<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application\Query;

use App\Order\Application\Query\GetOrder\GetOrderHandler;
use App\Order\Application\Query\GetOrder\GetOrderQuery;
use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Model\OrderStatus;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class GetOrderHandlerTest extends TestCase
{
    public function testHandleReturnsOrderView(): void
    {
        $order = Order::reconstitute(
            Uuid::v7(),
            1,
            42,
            [OrderItem::create(1, 2, 9.99)],
            OrderStatus::Pending,
            new \DateTimeImmutable(),
        );

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(1)->willReturn($order);

        $view = new GetOrderHandler($repository)->handle(new GetOrderQuery(1));

        $this->assertInstanceOf(OrderView::class, $view);
        $this->assertSame(1, $view->id);
        $this->assertSame(42, $view->userId);
        $this->assertSame('pending', $view->status);
        $this->assertCount(1, $view->items);
    }

    public function testHandleThrowsWhenOrderNotFound(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(OrderNotFoundException::class);

        new GetOrderHandler($repository)->handle(new GetOrderQuery(99));
    }
}
