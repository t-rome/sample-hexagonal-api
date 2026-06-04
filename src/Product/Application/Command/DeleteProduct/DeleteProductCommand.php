<?php

declare(strict_types=1);

namespace App\Product\Application\Command\DeleteProduct;

final readonly class DeleteProductCommand
{
    public function __construct(
        public int $id,
    ) {
    }
}
