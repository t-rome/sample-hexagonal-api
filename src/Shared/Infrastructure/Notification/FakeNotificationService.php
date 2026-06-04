<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Domain\NotificationServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Stub adapter — writes to the application log.
 * Replace with a real provider adapter (e.g. MailerNotificationService, SmsNotificationService)
 * that delivers the message via email, push notification, SMS, etc.
 */
final readonly class FakeNotificationService implements NotificationServiceInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function notify(int $userId, string $subject, string $message): void
    {
        $this->logger->info($subject, ['userId' => $userId, 'message' => $message]);
    }
}
