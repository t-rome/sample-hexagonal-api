<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AutoconfigureTag('app.exception_mapper')]
interface ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool;

    public function toResponse(\Throwable $exception): JsonResponse;
}
