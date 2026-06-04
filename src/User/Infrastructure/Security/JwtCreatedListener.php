<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JwtCreatedListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [Events::JWT_CREATED => 'onJwtCreated'];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $payload['jti'] = bin2hex(random_bytes(16));
        $event->setData($payload);
    }
}
