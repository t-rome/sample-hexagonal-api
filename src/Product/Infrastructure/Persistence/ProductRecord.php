<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine ORM entity for persisting products to the database.
 *
 * This class exists only in Infrastructure and carries all ORM annotations.
 * It is intentionally separate from the Product domain model, which must stay
 * free of persistence concerns. The ProductMapper translates between the two.
 * Never use this class outside of the persistence layer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'product')]
class ProductRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description;

    #[ORM\Column]
    public float $price;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(options: ['default' => 0])]
    public int $stock = 0;
}
