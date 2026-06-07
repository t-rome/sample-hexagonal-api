<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Infrastructure\Persistence\UserRecord;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasher->hashPassword(new UserRecord(), $plainPassword);
    }
}
