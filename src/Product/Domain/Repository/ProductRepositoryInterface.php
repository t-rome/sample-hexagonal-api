<?php

declare(strict_types=1);

namespace App\Product\Domain\Repository;

use App\Product\Domain\Model\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    public function save(Product $product): Product;

    public function delete(Product $product): void;
}
