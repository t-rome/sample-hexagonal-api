<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Application port for the read side of CQRS.
 *
 * Queries express an intent to read state without side effects (e.g. GetProductQuery).
 * Each query must have exactly one handler registered; asking with an unknown
 * query is a programming error, not a runtime condition.
 *
 * Handlers must be pure reads — no state mutations should occur inside a query handler.
 */
interface QueryBusInterface
{
    public function ask(object $query): mixed;
}
