<?php

declare(strict_types=1);

namespace App\Product\Application\Query\GetProduct;

use App\Product\Application\Query\ReadModel\ProductView;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class GetProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {
    }

    public function handle(GetProductQuery $query): ProductView
    {
        $product = $this->repository->findById($query->id);

        if (null === $product) {
            throw new ProductNotFoundException($query->id);
        }

        return ProductView::fromDomain($product);
    }
}
