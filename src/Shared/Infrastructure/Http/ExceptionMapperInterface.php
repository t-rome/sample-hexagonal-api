<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Strategy interface for mapping a domain or framework exception to a JSON HTTP response.
 *
 * Each bounded context provides its own mapper (e.g. OrderExceptionMapper,
 * ProductExceptionMapper) that handles the exceptions it owns. This keeps error
 * handling modular — a new module adds its own mapper without touching existing ones.
 *
 * The #[AutoconfigureTag] attribute automatically tags every implementing class with
 * 'app.exception_mapper'. ApiExceptionSubscriber receives all tagged mappers via a
 * tagged iterator (configured in services.yaml) and tries each one in turn until it
 * finds a mapper that supports the thrown exception.
 */
#[AutoconfigureTag('app.exception_mapper')]
interface ExceptionMapperInterface
{
    /** Returns true if this mapper knows how to handle the given exception. */
    public function supports(\Throwable $exception): bool;

    /** Converts the exception into a JSON response with an appropriate HTTP status code. */
    public function toResponse(\Throwable $exception): JsonResponse;
}
