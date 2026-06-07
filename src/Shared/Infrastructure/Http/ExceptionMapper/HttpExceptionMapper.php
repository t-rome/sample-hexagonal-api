<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\ExceptionMapper;

use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class HttpExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        if ($exception instanceof AccessDeniedException) {
            return true;
        }

        return $exception instanceof HttpExceptionInterface
            && $exception->getPrevious() instanceof ValidationFailedException;
    }

    public function toResponse(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof AccessDeniedException) {
            return new JsonResponse(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        /** @var ValidationFailedException $validation */
        $validation = $exception->getPrevious();
        $violations = [];
        foreach ($validation->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse(
            ['error' => 'Validation failed', 'violations' => $violations],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
