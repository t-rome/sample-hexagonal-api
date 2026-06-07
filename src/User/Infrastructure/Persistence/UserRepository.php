<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter that implements the UserRepositoryInterface port.
 *
 * Translates between the User domain model and the Doctrine ORM layer using
 * UserMapper. Domain code never touches Doctrine directly — it only calls
 * methods on the port interface, keeping the domain free of persistence concerns.
 *
 * The save() method handles both INSERT and UPDATE:
 *   - new user (id === null)  → no existing record → Doctrine does INSERT
 *   - existing user (id set)  → fetch existing record first → Doctrine does UPDATE
 * Passing the existing record to the mapper preserves Doctrine's change-tracking
 * so only modified columns are written to the database.
 *
 * After saving, the domain object is reconstructed from the persisted record so
 * that any database-generated values (e.g. the auto-incremented id) are reflected
 * in the returned User.
 */
final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserMapper $mapper,
    ) {
    }

    public function findById(int $id): ?User
    {
        $record = $this->em->find(UserRecord::class, $id);

        return null !== $record ? $this->mapper->toDomain($record) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $record = $this->em->getRepository(UserRecord::class)->findOneBy(['email' => $email]);

        return null !== $record ? $this->mapper->toDomain($record) : null;
    }

    public function save(User $user): User
    {
        $existing = null !== $user->getId()
            ? $this->em->find(UserRecord::class, $user->getId())
            : null;

        $record = $this->mapper->toRecord($user, $existing);

        $this->em->persist($record);
        $this->em->flush();

        return $this->mapper->toDomain($record);
    }
}
