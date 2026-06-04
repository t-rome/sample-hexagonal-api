<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures;

use App\User\Infrastructure\Persistence\UserRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'user-admin';
    public const USER_REFERENCE = 'user-regular';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new UserRecord();
        $admin->email = 'admin@example.com';
        $admin->roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $admin->password = $this->hasher->hashPassword($admin, 'admin1234');
        $manager->persist($admin);
        $this->addReference(self::ADMIN_REFERENCE, $admin);

        $user = new UserRecord();
        $user->email = 'user@example.com';
        $user->roles = ['ROLE_USER'];
        $user->password = $this->hasher->hashPassword($user, 'secret123');
        $manager->persist($user);
        $this->addReference(self::USER_REFERENCE, $user);

        $manager->flush();
    }
}
