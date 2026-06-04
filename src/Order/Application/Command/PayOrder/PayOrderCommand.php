<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PayOrder;

final readonly class PayOrderCommand
{
    public function __construct(
        public int $orderId,
    ) {
    }
}
