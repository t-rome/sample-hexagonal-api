<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

final class PaymentFailedException extends \RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct(\sprintf('Payment failed: %s', $reason));
    }
}
