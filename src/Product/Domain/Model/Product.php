<?php

declare(strict_types=1);

namespace App\Product\Domain\Model;

use App\Product\Domain\Exception\InsufficientStockException;

class Product
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private ?string $description,
        private float $price,
        private readonly \DateTimeImmutable $createdAt,
        private int $stock,
    ) {
    }

    public static function create(string $name, ?string $description, float $price, int $stock = 0): self
    {
        return new self(null, $name, $description, $price, new \DateTimeImmutable(), $stock);
    }

    public static function reconstitute(
        int $id,
        string $name,
        ?string $description,
        float $price,
        \DateTimeImmutable $createdAt,
        int $stock,
    ): self {
        return new self($id, $name, $description, $price, $createdAt, $stock);
    }

    public function update(string $name, ?string $description, float $price): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
    }

    public function reserveStock(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new InsufficientStockException($this->id ?? throw new \LogicException('Cannot reserve stock on a product without id.'), $quantity, $this->stock);
        }

        $this->stock -= $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStock(): int
    {
        return $this->stock;
    }
}
