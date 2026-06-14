<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\Model\OrderStatus;
use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 1002,
    httpStatusCode: 409,
    message: 'Order "{{ id }}" cannot be paid: expected status "pending", got "{{ status }}".')]
final class OrderNotPayableException extends ApiBaseException
{
    public function __construct(int $id, OrderStatus $currentStatus, ?\Throwable $previous = null)
    {
        parent::__construct(['id' => (string) $id, 'status' => $currentStatus->value], $previous);
    }
}
