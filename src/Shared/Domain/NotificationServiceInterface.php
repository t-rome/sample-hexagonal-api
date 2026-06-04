<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface NotificationServiceInterface
{
    public function notify(int $userId, string $subject, string $message): void;
}
