<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Domain\Model\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class OrderRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    public Uuid $uuid;

    #[ORM\Column(name: 'user_id')]
    public int $userId;

    #[ORM\Column(enumType: OrderStatus::class)]
    public OrderStatus $status;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    /** @var Collection<int, OrderItemRecord> */
    #[ORM\OneToMany(targetEntity: OrderItemRecord::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }
}
