<?php

declare(strict_types=1);

namespace App\Tests\Shared\Fixture;

use App\Order\Domain\Model\OrderStatus;
use App\Order\Infrastructure\Persistence\OrderItemRecord;
use App\Order\Infrastructure\Persistence\OrderRecord;
use App\Product\Infrastructure\Persistence\ProductRecord;
use App\User\Infrastructure\Persistence\UserRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class OrderFixture extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [UserFixture::class, ProductFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixture::USER_REF, UserRecord::class);
        $laptop = $this->getReference(ProductFixture::LAPTOP_ID_REF, ProductRecord::class);
        $mouse = $this->getReference(ProductFixture::MOUSE_ID_REF, ProductRecord::class);

        $order = new OrderRecord();
        $order->uuid = Uuid::v7();
        $order->userId = $user->id;
        $order->status = OrderStatus::Pending;
        $order->createdAt = new \DateTimeImmutable('2026-05-03T10:00:00+00:00');

        $laptopItem = new OrderItemRecord();
        $laptopItem->order = $order;
        $laptopItem->productId = $laptop->id;
        $laptopItem->quantity = 1;
        $laptopItem->unitPrice = $laptop->price;
        $order->items->add($laptopItem);

        $mouseItem = new OrderItemRecord();
        $mouseItem->order = $order;
        $mouseItem->productId = $mouse->id;
        $mouseItem->quantity = 2;
        $mouseItem->unitPrice = $mouse->price;
        $order->items->add($mouseItem);

        $manager->persist($order);
        $manager->flush();
    }
}
