<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JwtBlocklistListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly JwtBlocklist $blocklist,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [Events::JWT_DECODED => 'onJwtDecoded'];
    }

    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (!isset($payload['jti'])) {
            return;
        }

        if ($this->blocklist->contains($payload['jti'])) {
            $event->markAsInvalid();
        }
    }
}
