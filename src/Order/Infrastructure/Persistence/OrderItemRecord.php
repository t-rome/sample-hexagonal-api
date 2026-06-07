<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine ORM entity for persisting order line items.
 *
 * Mirrors the OrderItem domain object but with ORM annotations and a surrogate
 * database id. The OrderItemMapper translates between the two representations.
 * Never use this class outside of the persistence layer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItemRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderRecord::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false)]
    public OrderRecord $order;

    #[ORM\Column]
    public int $productId;

    #[ORM\Column]
    public int $quantity;

    #[ORM\Column]
    public float $unitPrice;
}
