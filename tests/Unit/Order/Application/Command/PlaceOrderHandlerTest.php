<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application\Command;

use App\Order\Application\Command\PlaceOrder\OrderItemData;
use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Order\Application\Command\PlaceOrder\PlaceOrderHandler;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Model\OrderStatus;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlaceOrderHandlerTest extends TestCase
{
    private function makeSavedOrder(): Order
    {
        return Order::reconstitute(
            Uuid::v7(),
            1,
            42,
            [OrderItem::create(1, 2, 9.99)],
            OrderStatus::Pending,
            new \DateTimeImmutable(),
        );
    }

    public function testHandlePlacesOrderAndDispatchesEvent(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 9.99, new \DateTimeImmutable(), 0);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())->method('findById')->with(1)->willReturn($product);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Order::class))
            ->willReturn($this->makeSavedOrder());

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $handler = new PlaceOrderHandler($orderRepo, $productRepo, $dispatcher);
        $result = $handler->handle(new PlaceOrderCommand(42, [new OrderItemData(1, 2)]));

        $this->assertSame(1, $result->getId());
        $this->assertSame(42, $result->getUserId());
    }

    public function testHandleThrowsWhenProductNotFound(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $orderRepo = $this->createStub(OrderRepositoryInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);

        $this->expectException(ProductNotFoundException::class);

        new PlaceOrderHandler($orderRepo, $productRepo, $dispatcher)
            ->handle(new PlaceOrderCommand(42, [new OrderItemData(99, 1)]));
    }
}
