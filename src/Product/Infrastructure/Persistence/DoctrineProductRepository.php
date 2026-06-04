<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence;

use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProductRepository implements ProductRepositoryInterface
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
