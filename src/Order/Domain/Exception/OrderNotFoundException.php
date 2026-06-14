<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 1001,
    httpStatusCode: 404,
    message: 'Order with id "{{ id }}" not found.')]
final class OrderNotFoundException extends ApiBaseException
{
    public function __construct(int $id, ?\Throwable $previous = null)
    {
        parent::__construct(['id' => (string) $id], $previous);
    }
}
