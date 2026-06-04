<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Psr\Cache\CacheItemPoolInterface;

final class JwtBlocklist
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function add(string $jti, \DateTimeImmutable $expiresAt): void
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
