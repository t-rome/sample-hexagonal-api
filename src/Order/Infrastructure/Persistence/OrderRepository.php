<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter that implements the OrderRepositoryInterface port.
 *
 * Translates between the Order domain model and the Doctrine ORM layer using
 * OrderMapper. Domain code never touches Doctrine directly — it only calls
 * methods on the port interface, keeping the domain free of persistence concerns.
 *
 * The save() method handles both INSERT and UPDATE:
 *   - new order (id === null)  → no existing record → Doctrine does INSERT
 *   - existing order (id set)  → fetch existing record first → Doctrine does UPDATE
 * Passing the existing record to the mapper preserves Doctrine's change-tracking
 * so only modified columns are written to the database.
 *
 * After saving, the domain object is reconstructed from the persisted record so
 * that any database-generated values (e.g. the auto-incremented id) are reflected
 * in the returned Order.
 */
final readonly class OrderRepository implements OrderRepositoryInterface
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
