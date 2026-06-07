<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Application port for the write side of CQRS.
 *
 * Commands express an intent to change state (e.g. PlaceOrderCommand).
 * Each command must have exactly one handler registered; dispatching an
 * unknown command is a programming error, not a runtime condition.
 *
 * Return values are allowed by the signature but should be avoided — prefer
 * reading state back through a query rather than returning data from a command.
 */
interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}
