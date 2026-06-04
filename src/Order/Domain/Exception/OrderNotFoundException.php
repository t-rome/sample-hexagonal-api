<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

final class OrderNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(\sprintf('Order with id "%d" not found.', $id));
    }
}
