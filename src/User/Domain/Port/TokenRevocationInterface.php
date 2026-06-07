<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

/**
 * Port for revoking JWT tokens (e.g. on logout).
 *
 * Defined in Domain so that the concept of "invalidating a session" is expressed
 * in business terms, independent of the storage mechanism. The adapter
 * (JwtBlocklist in Infrastructure) stores revoked token IDs in the database
 * until they naturally expire, so they are rejected on subsequent requests.
 */
interface TokenRevocationInterface
{
    public function revoke(string $tokenId, \DateTimeImmutable $expiresAt): void;
}
