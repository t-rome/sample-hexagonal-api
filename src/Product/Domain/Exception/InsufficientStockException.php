<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

final class InsufficientStockException extends \DomainException
{
    public function __construct(int $productId, int $requested, int $available)
    {
        parent::__construct(
            \sprintf(
                'Insufficient stock for product #%d: requested %d, available %d.',
                $productId,
                $requested,
                $available,
            ),
        );
    }
}
