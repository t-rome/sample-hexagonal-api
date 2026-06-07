<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Application\Command\RegisterUser\RegisterUserHandler;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Model\User;
use App\User\Domain\Port\PasswordHasherInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class RegisterUserHandlerTest extends TestCase
{
    public function testHandleRegistersAndReturnsUser(): void
    {
        $command = new RegisterUserCommand('user@example.com', 'plain-secret');
        $savedUser = User::reconstitute(1, 'user@example.com', 'hashed-secret', ['ROLE_USER']);

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('findByEmail')->with('user@example.com')->willReturn(null);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class))
            ->willReturn($savedUser);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->with('plain-secret')
            ->willReturn('hashed-secret');

        $result = (new RegisterUserHandler($repository, $hasher))->handle($command);

        $this->assertSame(1, $result->getId());
        $this->assertSame('user@example.com', $result->getEmail());
    }

    public function testHandleThrowsWhenEmailAlreadyExists(): void
    {
        $command = new RegisterUserCommand('existing@example.com', 'plain-secret');
        $existingUser = User::reconstitute(1, 'existing@example.com', 'hash', ['ROLE_USER']);

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('findByEmail')->with('existing@example.com')->willReturn($existingUser);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->never())->method('hash');

        $this->expectException(UserAlreadyExistsException::class);

        (new RegisterUserHandler($repository, $hasher))->handle($command);
    }
}
