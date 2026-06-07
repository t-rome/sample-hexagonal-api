<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Port for sending notifications to users.
 *
 * Defined in Domain to keep business logic decoupled from the delivery channel
 * (email, SMS, push notification, etc.). The concrete adapter lives in
 * Infrastructure and can be swapped without touching any domain or application code.
 *
 * During tests and local development, FakeNotificationService is injected instead,
 * which simply discards all notifications.
 */
interface NotificationServiceInterface
{
    public function notify(int $userId, string $subject, string $message): void;
}
