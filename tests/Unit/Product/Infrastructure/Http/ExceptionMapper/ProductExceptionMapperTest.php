<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http\ExceptionMapper;

use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Http\ExceptionMapper\ProductExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductExceptionMapperTest extends TestCase
{
    private ProductExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ProductExceptionMapper();
    }

    public function testSupportsProductExceptions(): void
    {
        $this->assertTrue($this->mapper->supports(new ProductNotFoundException(1)));
        $this->assertTrue($this->mapper->supports(new InsufficientStockException(1, 5, 2)));
    }

    public function testDoesNotSupportUnrelatedExceptions(): void
    {
        $this->assertFalse($this->mapper->supports(new \RuntimeException('unrelated')));
    }

    public function testProductNotFoundReturns404(): void
    {
        $response = $this->mapper->toResponse(new ProductNotFoundException(7));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('7', $response->getContent() ?: '');
    }

    public function testInsufficientStockReturns422(): void
    {
        $response = $this->mapper->toResponse(new InsufficientStockException(1, 10, 3));

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}
