<?php

declare(strict_types=1);

namespace App\Tests\Shared\Fixture;

use App\User\Infrastructure\Persistence\UserRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    public const USER_EMAIL = 'user@test.com';
    public const USER_PASSWORD = 'password123';
    public const USER_REF = 'user-default';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new UserRecord();
        $user->email = self::USER_EMAIL;
        $user->roles = ['ROLE_USER'];
        $user->password = $this->hasher->hashPassword($user, self::USER_PASSWORD);
        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::USER_REF, $user);
    }
}
