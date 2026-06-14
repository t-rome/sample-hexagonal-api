<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 1003,
    httpStatusCode: 402,
    message: 'Payment failed: {{ reason }}')]
final class PaymentFailedException extends ApiBaseException
{
    public function __construct(string $reason, ?\Throwable $previous = null)
    {
        parent::__construct(['reason' => $reason], $previous);
    }
}
