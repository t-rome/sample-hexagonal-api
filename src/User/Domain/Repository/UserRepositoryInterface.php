<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Model\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): User;
}
