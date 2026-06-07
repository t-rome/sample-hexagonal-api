<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Model;

use App\User\Domain\Model\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testRegisterCreatesUserWithPendingId(): void
    {
        $user = User::register('user@example.com', 'hashed-pw');

        $this->assertNull($user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame('hashed-pw', $user->getHashedPassword());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testReconstitute(): void
    {
        $user = User::reconstitute(7, 'admin@example.com', 'hashed', ['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertSame(7, $user->getId());
        $this->assertSame('admin@example.com', $user->getEmail());
        $this->assertSame('hashed', $user->getHashedPassword());
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testChangePasswordUpdatesHash(): void
    {
        $user = User::register('user@example.com', 'old-hash');
        $user->changePassword('new-hash');

        $this->assertSame('new-hash', $user->getHashedPassword());
    }
}
