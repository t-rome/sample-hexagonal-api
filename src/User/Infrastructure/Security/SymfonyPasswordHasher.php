<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Infrastructure\Persistence\UserRecord;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Adapter that implements the PasswordHasherInterface port using Symfony Security.
 *
 * Symfony's UserPasswordHasherInterface requires a UserInterface instance to
 * determine the correct hashing algorithm from the security.yaml configuration.
 * A blank UserRecord is passed solely to satisfy this API — the record carries
 * no user data and is discarded immediately after hashing.
 *
 * The domain model (User) never sees Symfony Security; it only works with the
 * already-hashed string returned by this adapter.
 */
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
