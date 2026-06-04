<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderItemInputDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $productId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $quantity,
    ) {
    }
}
