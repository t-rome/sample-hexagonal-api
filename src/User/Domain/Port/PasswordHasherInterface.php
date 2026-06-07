<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

/**
 * Port for hashing passwords.
 *
 * Defined in Domain to keep the User model independent of any specific hashing
 * library. The adapter (SymfonyPasswordHasher in Infrastructure) delegates to
 * Symfony's password hasher component, which can be swapped without changing
 * any domain or application code.
 */
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
