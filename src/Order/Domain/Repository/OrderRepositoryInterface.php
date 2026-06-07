<?php

declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Model\Order;

/**
 * Repository port for the Order aggregate.
 *
 * Defined in Domain so that application handlers can persist and retrieve Orders
 * without depending on any database technology. The concrete implementation
 * (OrderRepository in Infrastructure) uses Doctrine and is wired up via the
 * service container.
 *
 * This is the heart of the Ports & Adapters (Hexagonal) pattern: the domain
 * defines what it needs from persistence; Infrastructure provides the how.
 * Swapping the database engine requires only a new adapter — no domain code changes.
 */
interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    /** @return Order[] */
    public function findAll(): array;

    /** Persists a new or existing Order and returns it with any generated id filled in. */
    public function save(Order $order): Order;
}
