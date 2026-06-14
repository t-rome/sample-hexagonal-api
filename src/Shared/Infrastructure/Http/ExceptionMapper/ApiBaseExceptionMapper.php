<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\ExceptionMapper;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Generic mapper for all exceptions that extend ApiBaseException.
 *
 * Because every domain exception carries its own HTTP status code and application error
 * code via the #[ApiException] attribute, no per-exception branching is needed here.
 * Adding a new exception to any bounded context requires only the exception class itself —
 * this mapper picks it up automatically, satisfying the Open/Closed Principle.
 *
 * Framework exceptions (AccessDeniedException, ValidationFailedException) that cannot
 * extend ApiBaseException are handled separately by HttpExceptionMapper.
 */
final readonly class ApiBaseExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ApiBaseException;
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
