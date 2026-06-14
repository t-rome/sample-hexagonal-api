<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\ExceptionMapper;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Order\Domain\Model\OrderStatus;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Shared\Infrastructure\Http\ExceptionMapper\ApiBaseExceptionMapper;
use App\User\Domain\Exception\UserAlreadyExistsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiBaseExceptionMapperTest extends TestCase
{
    private ApiBaseExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ApiBaseExceptionMapper();
    }

    public function testSupportsAllDomainExceptions(): void
    {
        $this->assertTrue($this->mapper->supports(new OrderNotFoundException(1)));
        $this->assertTrue($this->mapper->supports(new OrderNotPayableException(1, OrderStatus::Confirmed)));
        $this->assertTrue($this->mapper->supports(new PaymentFailedException('declined')));
        $this->assertTrue($this->mapper->supports(new ProductNotFoundException(1)));
        $this->assertTrue($this->mapper->supports(new InsufficientStockException(1, 5, 2)));
        $this->assertTrue($this->mapper->supports(new UserAlreadyExistsException('user@example.com')));
    }

    public function testDoesNotSupportUnrelatedExceptions(): void
    {
        $this->assertFalse($this->mapper->supports(new \RuntimeException('unrelated')));
    }

    public function testResponseUsesStatusCodeAndErrorCodeFromException(): void
    {
        $response = $this->mapper->toResponse(new OrderNotFoundException(42));
        $body = json_decode($response->getContent() ?: '{}', true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(1001, $body['code']);
        $this->assertStringContainsString('42', $body['error']);
    }

    public function testEachExceptionCarriesItsOwnStatusAndCode(): void
    {
        $cases = [
            [new OrderNotFoundException(1),                              Response::HTTP_NOT_FOUND,            1001],
            [new OrderNotPayableException(1, OrderStatus::Confirmed),   Response::HTTP_CONFLICT,             1002],
            [new PaymentFailedException('declined'),                     Response::HTTP_PAYMENT_REQUIRED,     1003],
            [new ProductNotFoundException(1),                            Response::HTTP_NOT_FOUND,            2001],
            [new InsufficientStockException(1, 10, 3),                  Response::HTTP_UNPROCESSABLE_ENTITY, 2002],
            [new UserAlreadyExistsException('user@example.com'),        Response::HTTP_CONFLICT,             3001],
        ];

        foreach ($cases as [$exception, $expectedStatus, $expectedCode]) {
            $response = $this->mapper->toResponse($exception);
            $body = json_decode($response->getContent() ?: '{}', true);

            $this->assertSame($expectedStatus, $response->getStatusCode());
            $this->assertSame($expectedCode, $body['code']);
        }
    }
}
