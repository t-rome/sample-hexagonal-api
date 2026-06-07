<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Doctrine ORM entity for persisting users to the database.
 *
 * Unlike OrderRecord and ProductRecord, this class also implements Symfony's
 * UserInterface and PasswordAuthenticatedUserInterface. This is required because
 * Symfony Security needs a UserInterface to load users for authentication and to
 * hash passwords via UserPasswordHasherInterface.
 *
 * The User domain model intentionally does NOT implement these interfaces — it must
 * stay free of framework dependencies. UserRecord acts as both the ORM entity and
 * the Symfony Security user, bridging the two worlds in Infrastructure only.
 *
 * The UserMapper translates between UserRecord and User in both directions.
 * Never use this class outside of the persistence and security layer.
 */
#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class UserRecord implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    public string $email = '';

    #[ORM\Column]
    public string $password = '';

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    public array $roles = [];

    public function getUserIdentifier(): string
    {
        return $this->email ?: throw new \LogicException('Email must not be empty.');
    }

    /** @return string[] */
    public function getRoles(): array
    {
        return array_values(array_unique(array_merge($this->roles, ['ROLE_USER'])));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
