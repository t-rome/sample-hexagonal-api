<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application\Command;

use App\Order\Application\Command\PayOrder\PayOrderCommand;
use App\Order\Application\Command\PayOrder\PayOrderHandler;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\PaymentFailedException;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderItem;
use App\Order\Domain\Model\OrderStatus;
use App\Order\Domain\PaymentGatewayInterface;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PayOrderHandlerTest extends TestCase
{
    private function makePendingOrder(): Order
    {
        return Order::reconstitute(
            Uuid::v7(),
            1,
            42,
            [OrderItem::create(1, 2, 9.99)],
            OrderStatus::Pending,
            new \DateTimeImmutable(),
        );
    }

    private function makeConfirmedOrder(): Order
    {
        return Order::reconstitute(
            Uuid::v7(),
            1,
            42,
            [OrderItem::create(1, 2, 9.99)],
            OrderStatus::Confirmed,
            new \DateTimeImmutable(),
        );
    }

    private function makeHandler(
        OrderRepositoryInterface $orderRepo,
        ?PaymentGatewayInterface $gateway = null,
        ?EventDispatcherInterface $dispatcher = null,
    ): PayOrderHandler {
        return new PayOrderHandler(
            $orderRepo,
            $gateway ?? $this->createStub(PaymentGatewayInterface::class),
            $dispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }

    public function testHandleChargesGatewayAndDispatchesEvent(): void
    {
        $pendingOrder = $this->makePendingOrder();
        $confirmedOrder = $this->makeConfirmedOrder();

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->once())->method('findById')->with(1)->willReturn($pendingOrder);
        $orderRepo->expects($this->once())->method('save')->willReturn($confirmedOrder);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->expects($this->once())->method('charge');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $result = $this->makeHandler($orderRepo, $gateway, $dispatcher)->handle(new PayOrderCommand(1));

        $this->assertSame(OrderStatus::Confirmed, $result->getStatus());
    }

    public function testHandleThrowsWhenOrderNotFound(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(OrderNotFoundException::class);

        $this->makeHandler($orderRepo)->handle(new PayOrderCommand(99));
    }

    public function testHandlePropagatesPaymentFailedException(): void
    {
        $orderRepo = $this->createStub(OrderRepositoryInterface::class);
        $orderRepo->method('findById')->willReturn($this->makePendingOrder());

        $gateway = $this->createStub(PaymentGatewayInterface::class);
        $gateway->method('charge')->willThrowException(new PaymentFailedException('card declined'));

        $this->expectException(PaymentFailedException::class);

        $this->makeHandler($orderRepo, $gateway)->handle(new PayOrderCommand(1));
    }
}
