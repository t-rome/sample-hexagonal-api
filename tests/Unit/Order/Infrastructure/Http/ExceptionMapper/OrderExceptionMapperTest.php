<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Infrastructure\Http\ExceptionMapper;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Order\Domain\Model\OrderStatus;
use App\Order\Infrastructure\Http\ExceptionMapper\OrderExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class OrderExceptionMapperTest extends TestCase
{
    private OrderExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OrderExceptionMapper();
    }

    public function testSupportsOrderExceptions(): void
    {
        $this->assertTrue($this->mapper->supports(new OrderNotFoundException(1)));
        $this->assertTrue($this->mapper->supports(new OrderNotPayableException(1, OrderStatus::Confirmed)));
        $this->assertTrue($this->mapper->supports(new PaymentFailedException('card declined')));
    }

    public function testDoesNotSupportUnrelatedExceptions(): void
    {
        $this->assertFalse($this->mapper->supports(new \RuntimeException('unrelated')));
    }

    public function testOrderNotFoundReturns404(): void
    {
        $response = $this->mapper->toResponse(new OrderNotFoundException(42));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('42', $response->getContent() ?: '');
    }

    public function testOrderNotPayableReturns409(): void
    {
        $response = $this->mapper->toResponse(new OrderNotPayableException(1, OrderStatus::Confirmed));

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testPaymentFailedReturns402(): void
    {
        $response = $this->mapper->toResponse(new PaymentFailedException('card declined'));

        $this->assertSame(Response::HTTP_PAYMENT_REQUIRED, $response->getStatusCode());
    }
}
