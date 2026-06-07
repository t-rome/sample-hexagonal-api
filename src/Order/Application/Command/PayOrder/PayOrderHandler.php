<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PayOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Port\PaymentGatewayInterface;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Shared\Domain\DomainEventPublisherInterface;

final readonly class PayOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayInterface $paymentGateway,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function handle(PayOrderCommand $command): Order
    {
        $order = $this->orderRepository->findById($command->orderId);

        if (null === $order) {
            throw new OrderNotFoundException($command->orderId);
        }

        $this->paymentGateway->charge($order->getUuid(), $order->getTotalPrice());

        $order->pay();
        $savedOrder = $this->orderRepository->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return $savedOrder;
    }
}
