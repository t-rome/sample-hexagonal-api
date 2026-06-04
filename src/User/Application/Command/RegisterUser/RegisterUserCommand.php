<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
    ) {
    }
}
