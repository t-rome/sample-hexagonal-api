<?php

declare(strict_types=1);

namespace App\Tests\Shared\Fixture;

use App\Product\Infrastructure\Persistence\ProductRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixture extends Fixture
{
    public const LAPTOP_ID_REF = 'product-laptop';
    public const MOUSE_ID_REF = 'product-mouse';

    public function load(ObjectManager $manager): void
    {
        $laptop = new ProductRecord();
        $laptop->name = 'Laptop Pro';
        $laptop->description = 'A powerful laptop';
        $laptop->price = 1499.99;
        $laptop->stock = 10;
        $laptop->createdAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $manager->persist($laptop);
        $this->addReference(self::LAPTOP_ID_REF, $laptop);

        $mouse = new ProductRecord();
        $mouse->name = 'Wireless Mouse';
        $mouse->description = null;
        $mouse->price = 29.99;
        $mouse->stock = 50;
        $mouse->createdAt = new \DateTimeImmutable('2026-01-02T10:00:00+00:00');
        $manager->persist($mouse);
        $this->addReference(self::MOUSE_ID_REF, $mouse);

        $manager->flush();
    }
}
