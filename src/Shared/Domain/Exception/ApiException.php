<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Declares the HTTP and application-level error metadata for a domain exception.
 *
 * Apply this attribute to every class that extends ApiBaseException. The base class reads
 * it via reflection at construction time to set the HTTP status code, the application-level
 * error code, and the message template. Message templates support {{ placeholder }}
 * interpolation against the context array passed to the constructor.
 *
 * Error code conventions:
 *   1xxx — Order domain
 *   2xxx — Product domain
 *   3xxx — User domain
 *   4xxx — Shared / framework (access control, validation)
 *
 * Example:
 *   #[ApiException(errorCode: 1001, httpStatusCode: 404, message: 'Order "{{ id }}" not found.')]
 *   final class OrderNotFoundException extends ApiBaseException { ... }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiException
{
    public function __construct(
        public readonly int $errorCode,
        public readonly int $httpStatusCode,
        public readonly string $message,
    ) {
    }
}
