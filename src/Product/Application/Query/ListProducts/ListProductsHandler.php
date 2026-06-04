<?php

declare(strict_types=1);

namespace App\Product\Application\Query\ListProducts;

use App\Product\Application\Query\GetProduct\ProductView;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {
    }

    /** @return ProductView[] */
    public function handle(ListProductsQuery $query): array
    {
        return array_map(
            static fn ($product) => ProductView::fromDomain($product),
            $this->repository->findAll(),
        );
    }
}
