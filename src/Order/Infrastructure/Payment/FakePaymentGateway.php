<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Payment;

use App\Order\Domain\Exception\PaymentFailedException;
use App\Order\Domain\Port\PaymentGatewayInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Stub adapter — succeeds by default.
 * Call FakePaymentGateway::willDecline() in tests to simulate a declined payment.
 * Replace with a real provider adapter (e.g. StripePaymentGateway, AdyenPaymentGateway)
 * that calls the provider SDK and throws PaymentFailedException on decline or network error.
 */
final class FakePaymentGateway implements PaymentGatewayInterface
{
    private static bool $decline = false;

    public static function willDecline(): void
    {
        self::$decline = true;
    }

    public static function willSucceed(): void
    {
        self::$decline = false;
    }

    public function charge(Uuid $orderUuid, float $amount): void
    {
        if (self::$decline) {
            throw new PaymentFailedException('card declined');
        }
    }
}
