<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\Query;

use App\Product\Application\Query\GetProduct\GetProductHandler;
use App\Product\Application\Query\GetProduct\GetProductQuery;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GetProductHandlerTest extends TestCase
{
    public function testHandleReturnsProductView(): void
    {
        $product = Product::reconstitute(1, 'Laptop', 'A laptop', 999.99, new \DateTimeImmutable(), 0);

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(1)->willReturn($product);

        $view = new GetProductHandler($repository)->handle(new GetProductQuery(1));

        $this->assertSame(1, $view->id);
        $this->assertSame('Laptop', $view->name);
        $this->assertSame(999.99, $view->price);
    }

    public function testHandleThrowsWhenNotFound(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        new GetProductHandler($repository)->handle(new GetProductQuery(99));
    }
}
