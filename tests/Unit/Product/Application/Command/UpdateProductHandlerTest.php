<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\Command;

use App\Product\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Product\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UpdateProductHandlerTest extends TestCase
{
    public function testHandleUpdatesProduct(): void
    {
        $existing = Product::reconstitute(1, 'Old', null, 10.0, new \DateTimeImmutable(), 0);
        $updated = Product::reconstitute(1, 'New', 'Desc', 20.0, new \DateTimeImmutable(), 0);

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(1)->willReturn($existing);
        $repository->expects($this->once())->method('save')->willReturn($updated);

        $result = new UpdateProductHandler($repository)->handle(new UpdateProductCommand(1, 'New', 'Desc', 20.0));

        $this->assertSame('New', $result->getName());
        $this->assertSame(20.0, $result->getPrice());
    }

    public function testHandleThrowsWhenProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        new UpdateProductHandler($repository)->handle(new UpdateProductCommand(99, 'X', null, 1.0));
    }
}
