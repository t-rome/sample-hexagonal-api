<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application\EventHandler;

use App\Order\Application\EventHandler\NotifyUserOnOrderPaid;
use App\Order\Domain\Event\OrderPaid;
use App\Shared\Domain\NotificationServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NotifyUserOnOrderPaidTest extends TestCase
{
    public function testSubscribesToOrderPaid(): void
    {
        $this->assertArrayHasKey(OrderPaid::class, NotifyUserOnOrderPaid::getSubscribedEvents());
    }

    public function testNotifiesUserOnOrderPaid(): void
    {
        $notifier = $this->createMock(NotificationServiceInterface::class);
        $notifier->expects($this->once())
            ->method('notify')
            ->with(42, $this->stringContains('confirmed'), $this->stringContains('29.97'));

        $handler = new NotifyUserOnOrderPaid($notifier);
        $handler->onOrderPaid(new OrderPaid(
            orderUuid: Uuid::v7(),
            userId: 42,
            totalPrice: 29.97,
            paidAt: new \DateTimeImmutable(),
        ));
    }
}
