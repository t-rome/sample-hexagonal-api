<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrder;

use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Repository\OrderRepositoryInterface;

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {
    }

    public function handle(GetOrderQuery $query): OrderView
    {
        $order = $this->repository->findById($query->id);

        if (null === $order) {
            throw new OrderNotFoundException($query->id);
        }

        return OrderView::fromDomain($order);
    }
}
