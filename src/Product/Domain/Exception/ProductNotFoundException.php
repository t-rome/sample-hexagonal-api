<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

class ProductNotFoundException extends \DomainException
{
    public function __construct(int $id)
    {
        parent::__construct(\sprintf('Product with id "%d" not found.', $id));
    }
}
