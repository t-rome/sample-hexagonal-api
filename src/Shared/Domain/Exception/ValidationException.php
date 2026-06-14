<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Raised when controller input fails Symfony validation constraints.
 *
 * Created by HttpExceptionMapper when a ValidationFailedException is caught. Carries the
 * structured violations list alongside the standard error code and 422 status, so the
 * mapper can build the full response ({ code, error, violations }) through a single
 * exception instance — statusCode() and violations() — with no hard-coded values.
 *
 *   errorCode: 4002
 *   httpStatusCode: 422
 */
#[ApiException(
    errorCode: 4002,
    httpStatusCode: 422,
    message: 'Validation failed')]
final class ValidationException extends ApiBaseException
{
    /** @param list<array{field: string, message: string}> $violations */
    public function __construct(
        private readonly array $violations,
        ?\Throwable $previous = null,
    ) {
        parent::__construct([], $previous);
    }

    /** @return list<array{field: string, message: string}> */
    public function violations(): array
    {
        return $this->violations;
    }
}
