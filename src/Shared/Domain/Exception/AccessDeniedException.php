<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Raised when an authenticated user lacks the required permission.
 *
 * Created by HttpExceptionMapper when Symfony's AccessDeniedException is caught. Wrapping
 * it in a domain exception lets the mapper call statusCode() and errorCode() consistently,
 * with no hard-coded HTTP status code in the mapper itself.
 *
 *   errorCode: 4001
 *   httpStatusCode: 403
 */
#[ApiException(
    errorCode: 4001,
    httpStatusCode: 403,
    message: 'Access denied.')]
final class AccessDeniedException extends ApiBaseException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct([], $previous);
    }
}
