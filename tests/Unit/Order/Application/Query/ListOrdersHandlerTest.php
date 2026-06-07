<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application\Query;

use App\Order\Application\Query\ListOrders\ListOrdersHandler;
use App\Order\Application\Query\ListOrders\ListOrdersQuery;
use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Model\OrderStatus;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ListOrdersHandlerTest extends TestCase
{
    private function makeOrder(int $id, int $userId): Order
    {
        return Order::reconstitute(
            Uuid::v7(),
            $id,
            $userId,
            [OrderItem::create(1, 1, 9.99)],
            OrderStatus::Pending,
            new \DateTimeImmutable(),
        );
    }

    public function testHandleReturnsOrderViews(): void
    {
        $orders = [$this->makeOrder(1, 42), $this->makeOrder(2, 43)];

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())->method('findAll')->willReturn($orders);

        $views = new ListOrdersHandler($repository)->handle(new ListOrdersQuery());

        $this->assertCount(2, $views);
        $this->assertContainsOnlyInstancesOf(OrderView::class, $views);
        $this->assertSame(1, $views[0]->id);
        $this->assertSame(2, $views[1]->id);
    }

    public function testHandleReturnsEmptyArrayWhenNoOrders(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())->method('findAll')->willReturn([]);

        $views = new ListOrdersHandler($repository)->handle(new ListOrdersQuery());

        $this->assertSame([], $views);
    }
}
