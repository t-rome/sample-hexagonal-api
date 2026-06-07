<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBusInterface;
use Psr\Container\ContainerInterface;

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
