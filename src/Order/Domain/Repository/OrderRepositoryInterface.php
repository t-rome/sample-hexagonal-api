<?php

declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Model\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    /** @return Order[] */
    public function findAll(): array;

    public function save(Order $order): Order;
}
