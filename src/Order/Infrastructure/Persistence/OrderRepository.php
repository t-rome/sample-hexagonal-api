<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderMapper $mapper,
    ) {
    }

    public function findById(int $id): ?Order
    {
        $record = $this->em->find(OrderRecord::class, $id);

        return null !== $record ? $this->mapper->toDomain($record) : null;
    }

    public function findAll(): array
    {
        $records = $this->em->getRepository(OrderRecord::class)->findAll();

        return array_map($this->mapper->toDomain(...), $records);
    }

    public function save(Order $order): Order
    {
        $existing = null !== $order->getId()
            ? $this->em->find(OrderRecord::class, $order->getId())
            : null;

        $record = $this->mapper->toRecord($order, $existing);

        $this->em->persist($record);
        $this->em->flush();

        return $this->mapper->toDomain($record);
    }
}
