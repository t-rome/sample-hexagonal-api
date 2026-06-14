<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 2002,
    httpStatusCode: 422,
    message: 'Insufficient stock for product #{{ productId }}: requested {{ requested }}, available {{ available }}.')]
final class InsufficientStockException extends ApiBaseException
{
    public function __construct(int $productId, int $requested, int $available, ?\Throwable $previous = null)
    {
        parent::__construct(
            ['productId' => (string) $productId, 'requested' => (string) $requested, 'available' => (string) $available],
            $previous,
        );
    }
}
