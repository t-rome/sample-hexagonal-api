<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Port\TokenRevocationInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Adapter that implements the TokenRevocationInterface port using a PSR-6 cache.
 *
 * On logout, the JWT token's unique id (jti claim) is stored in the cache with
 * an expiry matching the token's own expiry. This means the blocklist entry is
 * automatically evicted once the token would have expired anyway — no manual
 * cleanup required.
 *
 * On each authenticated request, JwtBlocklistListener calls contains() to check
 * whether the presented token's jti has been revoked. If it has, the request is
 * rejected with a 401 before reaching any controller.
 */
final readonly class JwtBlocklist implements TokenRevocationInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function revoke(string $jti, \DateTimeImmutable $expiresAt): void
    {
        $item = $this->cache->getItem($this->key($jti));
        $item->set(true);
        $item->expiresAt($expiresAt);
        $this->cache->save($item);
    }

    public function contains(string $jti): bool
    {
        return $this->cache->getItem($this->key($jti))->isHit();
    }

    private function key(string $jti): string
    {
        return 'jwt_blocklist_'.$jti;
    }
}
