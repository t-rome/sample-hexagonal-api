<?php

declare(strict_types=1);

namespace App\Product\Application\Command\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public int $stock = 0,
    ) {
    }
}
