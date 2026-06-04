<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\Model\OrderStatus;

final class OrderNotPayableException extends \RuntimeException
{
    public function __construct(int $id, OrderStatus $currentStatus)
    {
        parent::__construct(\sprintf(
            'Order "%d" cannot be paid: expected status "pending", got "%s".',
            $id,
            $currentStatus->value,
        ));
    }
}
