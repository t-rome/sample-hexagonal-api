<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
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
