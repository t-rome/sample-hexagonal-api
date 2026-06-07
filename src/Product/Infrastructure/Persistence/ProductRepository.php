<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence;

use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter that implements the ProductRepositoryInterface port.
 *
 * Translates between the Product domain model and the Doctrine ORM layer using
 * ProductMapper. Domain code never touches Doctrine directly — it only calls
 * methods on the port interface, keeping the domain free of persistence concerns.
 *
 * The save() method handles both INSERT and UPDATE:
 *   - new product (id === null)  → no existing record → Doctrine does INSERT
 *   - existing product (id set)  → fetch existing record first → Doctrine does UPDATE
 * Passing the existing record to the mapper preserves Doctrine's change-tracking
 * so only modified columns are written to the database.
 *
 * After saving, the domain object is reconstructed from the persisted record so
 * that any database-generated values (e.g. the auto-incremented id) are reflected
 * in the returned Product.
 */
final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductMapper $mapper,
    ) {
    }

    public function findById(int $id): ?Product
    {
        $record = $this->em->find(ProductRecord::class, $id);

        return null !== $record ? $this->mapper->toDomain($record) : null;
    }

    public function findAll(): array
    {
        $records = $this->em->getRepository(ProductRecord::class)->findAll();

        return array_map($this->mapper->toDomain(...), $records);
    }

    public function save(Product $product): Product
    {
        $existing = null !== $product->getId()
            ? $this->em->find(ProductRecord::class, $product->getId())
            : null;

        $record = $this->mapper->toRecord($product, $existing);

        $this->em->persist($record);
        $this->em->flush();

        return $this->mapper->toDomain($record);
    }

    public function delete(Product $product): void
    {
        $record = $this->em->find(ProductRecord::class, $product->getId());

        if (null !== $record) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }
}
