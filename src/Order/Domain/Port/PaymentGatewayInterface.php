<?php

declare(strict_types=1);

namespace App\Order\Domain\Port;

use App\Order\Domain\Exception\PaymentFailedException;
use Symfony\Component\Uid\Uuid;

/**
 * Port for charging a payment for an order.
 *
 * Lives in Domain so that payment logic stays decoupled from any specific payment
 * provider (Stripe, PayPal, etc.). The concrete adapter is injected by the service
 * container — FakePaymentGateway during tests, a real provider in production.
 */
interface PaymentGatewayInterface
{
    /** @throws PaymentFailedException when the provider declines or errors */
    public function charge(Uuid $orderUuid, float $amount): void;
}
