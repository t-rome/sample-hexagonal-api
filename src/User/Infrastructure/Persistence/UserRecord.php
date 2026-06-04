<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
