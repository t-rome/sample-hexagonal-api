<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\ExceptionMapper;

use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class UserExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof UserAlreadyExistsException;
    }

    public function toResponse(\Throwable $exception): JsonResponse
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
    }
}
