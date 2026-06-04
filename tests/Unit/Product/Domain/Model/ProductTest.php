<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Model;

use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Model\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testCreate(): void
    {
        $product = Product::create('Laptop', 'A laptop', 999.99, 10);

        $this->assertNull($product->getId());
        $this->assertSame('Laptop', $product->getName());
        $this->assertSame('A laptop', $product->getDescription());
        $this->assertSame(999.99, $product->getPrice());
        $this->assertSame(10, $product->getStock());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
    }

    public function testCreateDefaultsStockToZero(): void
    {
        $product = Product::create('Laptop', null, 999.99);

        $this->assertSame(0, $product->getStock());
    }

    public function testReconstitute(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-01');
        $product = Product::reconstitute(42, 'Mouse', null, 29.99, $createdAt, 50);

        $this->assertSame(42, $product->getId());
        $this->assertSame('Mouse', $product->getName());
        $this->assertNull($product->getDescription());
        $this->assertSame(29.99, $product->getPrice());
        $this->assertSame($createdAt, $product->getCreatedAt());
        $this->assertSame(50, $product->getStock());
    }

    public function testUpdate(): void
    {
        $product = Product::create('Old Name', null, 10.0, 5);
        $product->update('New Name', 'New desc', 20.0);

        $this->assertSame('New Name', $product->getName());
        $this->assertSame('New desc', $product->getDescription());
        $this->assertSame(20.0, $product->getPrice());
        $this->assertSame(5, $product->getStock());
    }

    public function testReserveStockReducesStock(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 10);

        $product->reserveStock(3);

        $this->assertSame(7, $product->getStock());
    }

    public function testReserveFullStock(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 5);

        $product->reserveStock(5);

        $this->assertSame(0, $product->getStock());
    }

    public function testReserveStockThrowsWhenInsufficient(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 2);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Insufficient stock for product #1: requested 5, available 2.');

        $product->reserveStock(5);
    }

    public function testReserveStockThrowsWhenEmpty(): void
    {
        $product = Product::reconstitute(1, 'Laptop', null, 999.99, new \DateTimeImmutable(), 0);

        $this->expectException(InsufficientStockException::class);

        $product->reserveStock(1);
    }
}
