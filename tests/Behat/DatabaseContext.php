<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Product\Infrastructure\Persistence\ProductRecord;
use App\User\Infrastructure\Persistence\UserRecord;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DatabaseContext implements Context
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct()
    {
        $container = KernelBoot::container();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);
    }

    #[BeforeScenario]
    public function resetDatabase(): void
    {
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET session_replication_role = replica');
        new ORMExecutor($this->em, new ORMPurger($this->em))->execute([]);
        $connection->executeStatement('SET session_replication_role = DEFAULT');
        $this->em->clear();
    }

    #[Given('a user exists with email :email and password :password')]
    public function aUserExistsWithEmailAndPassword(string $email, string $password): void
    {
        $user = new UserRecord();
        $user->email = $email;
        $user->roles = ['ROLE_USER'];
        $user->password = $this->hasher->hashPassword($user, $password);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }

    #[Given('the following products exist:')]
    public function theFollowingProductsExist(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $row) {
            $product = new ProductRecord();
            $product->name = $row['name'];
            $product->description = ($row['description'] ?? '') ?: null;
            $product->price = (float) $row['price'];
            $product->stock = isset($row['stock']) ? (int) $row['stock'] : 0;
            $product->createdAt = new \DateTimeImmutable();
            $this->em->persist($product);
        }
        $this->em->flush();
        $this->em->clear();
    }
}
