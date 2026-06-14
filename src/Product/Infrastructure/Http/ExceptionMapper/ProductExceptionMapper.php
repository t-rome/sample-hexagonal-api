<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http\ExceptionMapper;

use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Maps Product domain exceptions to JSON HTTP responses.
 *
 * Each bounded context owns its exception mapping — this class handles only the exceptions
 * thrown by the Product domain. HTTP status codes and error codes are not hardcoded here;
 * they are declared via #[ApiException] on each exception class and read through statusCode()
 * and errorCode(), keeping this mapper free of any response-shape logic.
 *
 *   ProductNotFoundException   → 404  (errorCode: 2001)
 *   InsufficientStockException → 422  (errorCode: 2002)
 */
final readonly class ProductExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ProductNotFoundException
            || $exception instanceof InsufficientStockException;
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
