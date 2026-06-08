<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Model\User;
use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    public function handle(RegisterUserCommand $command): User
    {
        if (null !== $this->repository->findByEmail($command->email)) {
            throw new UserAlreadyExistsException($command->email);
        }

        $user = User::register($command->email, $this->hasher->hash($command->plainPassword));

        return $this->repository->save($user);
    }
}
