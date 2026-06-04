<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrder;

final readonly class GetOrderQuery
{
    public function __construct(public int $id)
    {
    }
}
