<?php

declare(strict_types=1);

namespace App\Product\Application\Command\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public float $price,
    ) {
    }
}
