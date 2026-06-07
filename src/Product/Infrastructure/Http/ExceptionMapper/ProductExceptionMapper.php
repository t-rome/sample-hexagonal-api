<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http\ExceptionMapper;

use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
