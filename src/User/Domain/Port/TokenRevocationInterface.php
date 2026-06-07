<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

interface TokenRevocationInterface
{
    public function revoke(string $tokenId, \DateTimeImmutable $expiresAt): void;
}
