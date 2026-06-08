<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\EventHandler;

use App\Order\Domain\Event\OrderPlaced;
use App\Order\Domain\Model\OrderItem;
use App\Product\Application\EventHandler\ReserveStockOnOrderPlacedHandler;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class ReserveStockOnOrderPlacedHandlerTest extends TestCase
{
    private ProductRepositoryInterface&Stub $repository;
    private ReserveStockOnOrderPlacedHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(ProductRepositoryInterface::class);
        $this->handler = new ReserveStockOnOrderPlacedHandler($this->repository);
    }

    public function testReservesStockForEachItem(): void
    {
        $laptop = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 10);
        $mouse = Product::reconstitute(2, 'Mouse', null, 29.99, new \DateTimeImmutable(), 50);

        $this->repository->method('findById')->willReturnMap([
            [1, $laptop],
            [2, $mouse],
        ]);

        $savedProducts = [];
        $this->repository->method('save')->willReturnCallback(static function (Product $p) use (&$savedProducts) {
            $savedProducts[] = $p;

            return $p;
        });

        $event = new OrderPlaced(
            orderUuid: new UuidV7(),
            userId: 42,
            totalPrice: 1029.98,
            placedAt: new \DateTimeImmutable(),
            items: [
                OrderItem::create(1, 2, 999.99),
                OrderItem::create(2, 1, 29.99),
            ],
        );

        $this->handler->handle($event);

        $this->assertSame(8, $laptop->getStock());
        $this->assertSame(49, $mouse->getStock());
        $this->assertCount(2, $savedProducts);
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $event = new OrderPlaced(
            orderUuid: new UuidV7(),
            userId: 1,
            totalPrice: 10.0,
            placedAt: new \DateTimeImmutable(),
            items: [OrderItem::create(99, 1, 10.0)],
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Product #99 not found during stock reservation.');

        $this->handler->handle($event);
    }

    public function testThrowsWhenStockInsufficient(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 1);
        $this->repository->method('findById')->willReturn($product);

        $event = new OrderPlaced(
            orderUuid: new UuidV7(),
            userId: 1,
            totalPrice: 1999.98,
            placedAt: new \DateTimeImmutable(),
            items: [OrderItem::create(1, 2, 999.99)],
        );

        $this->expectException(InsufficientStockException::class);

        $this->handler->handle($event);
    }
}
