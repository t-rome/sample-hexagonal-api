<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlaceOrderDto
{
    /** @param OrderItemInputDto[] $items */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $items,
    ) {
    }
}
