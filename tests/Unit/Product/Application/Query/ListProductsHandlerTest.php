<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\Query;

use App\Product\Application\Query\ListProducts\ListProductsHandler;
use App\Product\Application\Query\ListProducts\ListProductsQuery;
use App\Product\Application\Query\ReadModel\ProductView;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ListProductsHandlerTest extends TestCase
{
    public function testHandleReturnsProductViews(): void
    {
        $products = [
            Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 5),
            Product::reconstitute(2, 'Mouse', 'Wireless', 29.99, new \DateTimeImmutable(), 20),
        ];

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findAll')->willReturn($products);

        $views = new ListProductsHandler($repository)->handle(new ListProductsQuery());

        $this->assertCount(2, $views);
        $this->assertContainsOnlyInstancesOf(ProductView::class, $views);
        $this->assertSame(1, $views[0]->id);
        $this->assertSame('Mouse', $views[1]->name);
    }

    public function testHandleReturnsEmptyArrayWhenNoProducts(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findAll')->willReturn([]);

        $views = new ListProductsHandler($repository)->handle(new ListProductsQuery());

        $this->assertSame([], $views);
    }
}
