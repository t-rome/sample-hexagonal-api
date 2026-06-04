<?php

declare(strict_types=1);

namespace App\Product\Application\Command\DeleteProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {
    }

    public function handle(DeleteProductCommand $command): void
    {
        $product = $this->repository->findById($command->id);

        if (null === $product) {
            throw new ProductNotFoundException($command->id);
        }

        $this->repository->delete($product);
    }
}
