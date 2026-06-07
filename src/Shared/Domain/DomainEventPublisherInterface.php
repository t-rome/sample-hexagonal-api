<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface DomainEventPublisherInterface
{
    public function publish(DomainEvent $event): void;
}
