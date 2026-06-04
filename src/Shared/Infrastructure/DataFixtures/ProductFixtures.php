<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures;

use App\Product\Infrastructure\Persistence\ProductRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    private const PRODUCTS = [
        ['name' => 'Laptop Pro 15',    'description' => 'High-performance laptop with 32 GB RAM and 1 TB SSD.',  'price' => 1499.99, 'stock' => 10],
        ['name' => 'Wireless Mouse',   'description' => 'Ergonomic wireless mouse with 12-month battery life.',  'price' => 29.99,   'stock' => 50],
        ['name' => 'Mechanical Keyboard', 'description' => 'Compact TKL keyboard with Cherry MX Red switches.', 'price' => 89.90,   'stock' => 25],
        ['name' => '4K Monitor 27"',   'description' => null,                                                   'price' => 399.00,  'stock' => 8],
        ['name' => 'USB-C Hub',        'description' => '7-in-1 hub: HDMI, USB-A, SD card, PD charging.',       'price' => 49.95,   'stock' => 30],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRODUCTS as $data) {
            $product = new ProductRecord();
            $product->name = $data['name'];
            $product->description = $data['description'];
            $product->price = $data['price'];
            $product->stock = $data['stock'];
            $product->createdAt = new \DateTimeImmutable();

            $manager->persist($product);
        }

        $manager->flush();
    }
}
