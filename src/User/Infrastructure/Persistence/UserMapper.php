<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Model\User;

final class UserMapper
{
    public function toDomain(UserRecord $record): User
    {
        return User::reconstitute(
            id: $record->id ?? throw new \LogicException('UserRecord must have an id.'),
            email: $record->email,
            hashedPassword: $record->password,
            roles: $record->roles,
        );
    }

    public function toRecord(User $user, ?UserRecord $existing = null): UserRecord
    {
        $record = $existing ?? new UserRecord();
        $record->email = $user->getEmail();
        $record->password = $user->getHashedPassword();
        $record->roles = $user->getRoles();

        return $record;
    }
}
