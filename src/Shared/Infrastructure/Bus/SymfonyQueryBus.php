<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use Psr\Container\ContainerInterface;

final class SymfonyQueryBus implements QueryBusInterface
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
