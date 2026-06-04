<?php

declare(strict_types=1);

namespace App\Order\Domain\Port;

use App\Order\Domain\Exception\PaymentFailedException;
use Symfony\Component\Uid\Uuid;

interface PaymentGatewayInterface
{
    /** @throws PaymentFailedException */
    public function charge(Uuid $orderUuid, float $amount): void;
}
