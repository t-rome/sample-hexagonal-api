<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\ExceptionMapper;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\OrderNotPayableException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class OrderExceptionMapper implements ExceptionMapperInterface
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
