<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 2001,
    httpStatusCode: 404,
    message: 'Product with id "{{ id }}" not found.')]
final class ProductNotFoundException extends ApiBaseException
{
    public function __construct(int $id, ?\Throwable $previous = null)
    {
        parent::__construct(['id' => (string) $id], $previous);
    }
}
