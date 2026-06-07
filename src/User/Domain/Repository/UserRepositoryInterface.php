<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Model\User;

/**
 * Repository port for the User entity.
 *
 * Defined in Domain so that application handlers can persist and retrieve Users
 * without depending on any database technology. The concrete implementation
 * (UserRepository in Infrastructure) uses Doctrine and is wired up via the
 * service container.
 *
 * This is the heart of the Ports & Adapters (Hexagonal) pattern: the domain
 * defines what it needs from persistence; Infrastructure provides the how.
 * Swapping the database engine requires only a new adapter — no domain code changes.
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /** Persists a new or existing User and returns it with any generated id filled in. */
    public function save(User $user): User;
}
