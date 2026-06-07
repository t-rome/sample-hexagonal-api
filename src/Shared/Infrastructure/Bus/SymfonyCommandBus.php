<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBusInterface;
use Psr\Container\ContainerInterface;

/**
 * Symfony adapter for the CommandBusInterface port.
 *
 * Uses a service locator — a special DI container that holds only the explicitly
 * registered command → handler pairs. The locator is configured in services.yaml
 * using the !service_locator tag, which maps each Command class name to its Handler.
 *
 * Dispatch flow:
 *   Controller → CommandBusInterface::dispatch(command)
 *     → this class looks up the handler by command class name
 *     → calls handler->handle(command)
 *     → handler calls Aggregate methods, which mutate domain state
 *
 * The service locator (not the full DI container) is injected to keep the bus
 * fast and to make it explicit which commands are supported at a glance in services.yaml.
 */
final class SymfonyCommandBus implements CommandBusInterface
{
    public function __construct(private readonly ContainerInterface $locator)
    {
    }

    public function dispatch(object $command): mixed
    {
        $class = $command::class;

        if (!$this->locator->has($class)) {
            throw new \RuntimeException(\sprintf('No handler registered for command "%s".', $class));
        }

        return $this->locator->get($class)->handle($command);
    }
}
