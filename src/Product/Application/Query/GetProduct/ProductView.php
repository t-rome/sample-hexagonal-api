<?php

declare(strict_types=1);

namespace App\Product\Application\Query\GetProduct;

use App\Product\Domain\Model\Product;

final readonly class ProductView
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public float $price,
        public string $createdAt,
        public int $stock,
    ) {
    }

    public static function fromDomain(Product $product): self
    {
        return new self(
            id: $product->getId() ?? throw new \LogicException('Product must have an id to create a view.'),
            name: $product->getName(),
            description: $product->getDescription(),
            price: $product->getPrice(),
            createdAt: $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
            stock: $product->getStock(),
        );
    }
}
