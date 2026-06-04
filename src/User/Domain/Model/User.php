<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

class User
{
    private function __construct(
        private readonly ?int $id,
        private string $email,
        private string $hashedPassword,
        /** @var string[] */
        private array $roles,
    ) {
    }

    public static function register(string $email, string $hashedPassword): self
    {
        return new self(null, $email, $hashedPassword, ['ROLE_USER']);
    }

    /** @param string[] $roles */
    public static function reconstitute(int $id, string $email, string $hashedPassword, array $roles): self
    {
        return new self($id, $email, $hashedPassword, $roles);
    }

    public function changePassword(string $newHashedPassword): void
    {
        $this->hashedPassword = $newHashedPassword;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    /** @return string[] */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
