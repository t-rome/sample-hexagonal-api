<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\Command;

use App\Product\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Product\Application\Command\DeleteProduct\DeleteProductHandler;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class DeleteProductHandlerTest extends TestCase
{
    public function testHandleDeletesProduct(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 0);

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(1)->willReturn($product);
        $repository->expects($this->once())->method('delete')->with($product);

        new DeleteProductHandler($repository)->handle(new DeleteProductCommand(1));
    }

    public function testHandleThrowsWhenProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        new DeleteProductHandler($repository)->handle(new DeleteProductCommand(99));
    }
}
