<?php

declare(strict_types=1);

namespace App\Product\Domain\Repository;

use App\Product\Domain\Model\Product;

/**
 * Repository port for the Product entity.
 *
 * Defined in Domain so that application handlers can persist and retrieve Products
 * without depending on any database technology. The concrete implementation
 * (ProductRepository in Infrastructure) uses Doctrine and is wired up via the
 * service container.
 *
 * This is the heart of the Ports & Adapters (Hexagonal) pattern: the domain
 * defines what it needs from persistence; Infrastructure provides the how.
 * Swapping the database engine requires only a new adapter — no domain code changes.
 */
interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    /** Persists a new or existing Product and returns it with any generated id filled in. */
    public function save(Product $product): Product;

    public function delete(Product $product): void;
}
