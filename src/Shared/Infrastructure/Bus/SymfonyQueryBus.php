<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use Psr\Container\ContainerInterface;

/**
 * Symfony adapter for the QueryBusInterface port.
 *
 * Uses a service locator — a special DI container that holds only the explicitly
 * registered query → handler pairs. The locator is configured in services.yaml
 * using the !service_locator tag, which maps each Query class name to its Handler.
 *
 * Ask flow:
 *   Controller → QueryBusInterface::ask(query)
 *     → this class looks up the handler by query class name
 *     → calls handler->handle(query)
 *     → handler reads from the repository and returns a Read Model (DTO)
 *
 * Identical structure to SymfonyCommandBus but for the read side — kept separate
 * so that command and query flows are always independently traceable.
 */
final readonly class SymfonyQueryBus implements QueryBusInterface
{
    public function __construct(private readonly ContainerInterface $locator)
    {
    }

    public function ask(object $query): mixed
    {
        $class = $query::class;

        if (!$this->locator->has($class)) {
            throw new \RuntimeException(\sprintf('No handler registered for query "%s".', $class));
        }

        return $this->locator->get($class)->handle($query);
    }
}
