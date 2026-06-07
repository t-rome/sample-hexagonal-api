<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http\ExceptionMapper;

use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps Product domain exceptions to JSON HTTP responses.
 *
 * Each bounded context owns its exception mapping — this class handles only the
 * exceptions thrown by the Product domain, keeping error handling modular and
 * preventing any cross-context coupling.
 *
 *   ProductNotFoundException   → 404 Not Found
 *   InsufficientStockException → 422 Unprocessable Entity
 */
final class ProductExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ProductNotFoundException
            || $exception instanceof InsufficientStockException;
    }

    public function toResponse(\Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof ProductNotFoundException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND,
            ),
            default => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ),
        };
    }
}
