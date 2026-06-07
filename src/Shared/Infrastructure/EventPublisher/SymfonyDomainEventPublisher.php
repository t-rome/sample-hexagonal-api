<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventPublisher;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SymfonyDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function publish(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
