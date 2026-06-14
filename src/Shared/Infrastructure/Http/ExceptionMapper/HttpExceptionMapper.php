<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\ExceptionMapper;

use App\Shared\Domain\Exception\AccessDeniedException;
use App\Shared\Domain\Exception\ValidationException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as SymfonyAccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Mapper for Symfony framework exceptions: access denied and input validation failures.
 *
 * Handles two cases that are not domain-specific and therefore belong here in Shared:
 *
 * 1. SymfonyAccessDeniedException — thrown by Symfony Security (e.g. via denyAccessUnlessGranted()
 *    or a Voter) when the authenticated user lacks the required role or permission.
 *    → 403 Forbidden
 *
 * 2. Validation failure — Symfony wraps a ValidationFailedException inside an
 *    UnprocessableEntityHttpException when a controller DTO fails validation constraints.
 *    The actual violation list is retrieved via getPrevious() because Symfony's HTTP layer
 *    stores the original cause as the "previous" exception.
 *    → 422 Unprocessable Entity with a structured violations array
 */
final readonly class HttpExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        if ($exception instanceof SymfonyAccessDeniedException) {
            return true;
        }

        return $exception instanceof HttpExceptionInterface
            && $exception->getPrevious() instanceof ValidationFailedException;
    }

    public function toResponse(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof SymfonyAccessDeniedException) {
            $domainException = new AccessDeniedException($exception);

            return new JsonResponse(
                ['code' => $domainException->errorCode(), 'error' => $domainException->getMessage()],
                $domainException->statusCode(),
            );
        }

        /** @var ValidationFailedException $validation */
        $validation = $exception->getPrevious();
        $violations = [];
        foreach ($validation->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        $domainException = new ValidationException($violations, $exception);

        return new JsonResponse(
            ['code' => $domainException->errorCode(), 'error' => $domainException->getMessage(), 'violations' => $domainException->violations()],
            $domainException->statusCode(),
        );
    }
}
