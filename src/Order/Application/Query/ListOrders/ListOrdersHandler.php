<?php

declare(strict_types=1);

namespace App\Order\Application\Query\ListOrders;

use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Domain\Repository\OrderRepositoryInterface;

final readonly class ListOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {
    }

    /** @return OrderView[] */
    public function handle(ListOrdersQuery $query): array
    {
        return array_map(OrderView::fromDomain(...), $this->repository->findAll());
    }
}
