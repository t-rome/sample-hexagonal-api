<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\ExceptionMapper;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps Order domain exceptions to JSON HTTP responses.
 *
 * Each bounded context owns its exception mapping — this class handles only the
 * exceptions thrown by the Order domain, keeping error handling modular and
 * preventing any cross-context coupling.
 *
 *   OrderNotFoundException    → 404 Not Found
 *   OrderNotPayableException  → 409 Conflict   (order already paid or cancelled)
 *   PaymentFailedException    → 402 Payment Required
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
        return match (true) {
            $exception instanceof OrderNotFoundException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND,
            ),
            $exception instanceof OrderNotPayableException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            ),
            default => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_PAYMENT_REQUIRED,
            ),
        };
    }
}
