<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Model\User;

/**
 * Translates between the User domain model and the Doctrine ORM record.
 *
 * The domain model (User) is kept free of ORM annotations and persistence
 * concerns. This mapper acts as an anti-corruption layer between the two worlds:
 *
 * toDomain() — called when loading from the database; reconstructs a pure domain
 *              object via User::reconstitute(), bypassing normal business rules
 *              since the data was already validated when it was first saved.
 *
 * toRecord() — called before persisting; maps domain state onto the ORM entity.
 *              Accepts an existing record to allow Doctrine's change-tracking to
 *              detect which columns actually changed (UPDATE instead of INSERT).
 */
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
