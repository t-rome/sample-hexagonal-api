<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        public ?string $description,
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public float $price,
        #[Assert\PositiveOrZero]
        public int $stock = 0,
    ) {
    }
}
