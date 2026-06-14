<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\ExceptionMapper;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Maps User domain exceptions to JSON HTTP responses.
 *
 * Each bounded context owns its exception mapping — this class handles only the exceptions
 * thrown by the User domain. HTTP status codes and error codes are not hardcoded here;
 * they are declared via #[ApiException] on each exception class and read through statusCode()
 * and errorCode(), keeping this mapper free of any response-shape logic.
 *
 *   UserAlreadyExistsException → 409  (errorCode: 3001)
 */
final readonly class UserExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof UserAlreadyExistsException;
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
