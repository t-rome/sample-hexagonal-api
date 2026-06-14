<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\ExceptionMapper;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Maps Order domain exceptions to JSON HTTP responses.
 *
 * Each bounded context owns its exception mapping — this class handles only the exceptions
 * thrown by the Order domain. HTTP status codes and error codes are not hardcoded here;
 * they are declared via #[ApiException] on each exception class and read through statusCode()
 * and errorCode(), keeping this mapper free of any response-shape logic.
 *
 *   OrderNotFoundException   → 404  (errorCode: 1001)
 *   OrderNotPayableException → 409  (errorCode: 1002)
 *   PaymentFailedException   → 402  (errorCode: 1003)
 */
final readonly class OrderExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof OrderNotFoundException
            || $exception instanceof OrderNotPayableException
            || $exception instanceof PaymentFailedException;
    }

    public function toResponse(\Throwable $exception): JsonResponse
    {
        assert($exception instanceof ApiBaseException);

        return new JsonResponse(
            ['code' => $exception->errorCode(), 'error' => $exception->getMessage()],
            $exception->statusCode(),
        );
    }
}
