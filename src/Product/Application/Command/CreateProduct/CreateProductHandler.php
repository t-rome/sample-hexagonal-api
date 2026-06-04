<?php

declare(strict_types=1);

namespace App\Product\Application\Command\CreateProduct;

use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {
    }

    public function handle(CreateProductCommand $command): Product
    {
        $product = Product::create($command->name, $command->description, $command->price, $command->stock);

        return $this->repository->save($product);
    }
}
