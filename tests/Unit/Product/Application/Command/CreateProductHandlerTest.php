<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\Command;

use App\Product\Application\Command\CreateProduct\CreateProductCommand;
use App\Product\Application\Command\CreateProduct\CreateProductHandler;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class CreateProductHandlerTest extends TestCase
{
    public function testHandleCreatesAndReturnsProduct(): void
    {
        $command = new CreateProductCommand('Laptop', 'A laptop', 999.99);
        $savedProduct = Product::reconstitute(1, 'Laptop', 'A laptop', 999.99, new \DateTimeImmutable(), 0);

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Product::class))
            ->willReturn($savedProduct);

        $handler = new CreateProductHandler($repository);
        $result = $handler->handle($command);

        $this->assertSame(1, $result->getId());
        $this->assertSame('Laptop', $result->getName());
        $this->assertSame(999.99, $result->getPrice());
    }
}
