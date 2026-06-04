<?php

declare(strict_types=1);

namespace App\Product\Application\Command\UpdateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {
    }

    public function handle(UpdateProductCommand $command): Product
    {
        $product = $this->repository->findById($command->id);

        if (null === $product) {
            throw new ProductNotFoundException($command->id);
        }

        $product->update($command->name, $command->description, $command->price);

        return $this->repository->save($product);
    }
}
