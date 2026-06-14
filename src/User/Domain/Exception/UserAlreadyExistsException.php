<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ApiBaseException;
use App\Shared\Domain\Exception\ApiException;

#[ApiException(
    errorCode: 3001,
    httpStatusCode: 409,
    message: 'User with email "{{ email }}" already exists.')]
final class UserAlreadyExistsException extends ApiBaseException
{
    public function __construct(string $email, ?\Throwable $previous = null)
    {
        parent::__construct(['email' => $email], $previous);
    }
}
