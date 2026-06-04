<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\UserRecord;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function handle(RegisterUserCommand $command): User
    {
        if (null !== $this->repository->findByEmail($command->email)) {
            throw new UserAlreadyExistsException($command->email);
        }

        $hashed = $this->hasher->hashPassword(new UserRecord(), $command->plainPassword);

        $user = User::register($command->email, $hashed);

        return $this->repository->save($user);
    }
}
