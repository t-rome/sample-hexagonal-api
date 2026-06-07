<?php

declare(strict_types=1);

namespace App\Product\Application\Query\ReadModel;

use App\Product\Domain\Model\Product;

/**
 * Read model (DTO) returned by product query handlers.
 *
 * Query handlers return a view object instead of the domain model for two reasons:
 * 1. Decoupling: the API response shape is independent of domain internals — the
 *    domain can evolve without breaking the read API, and vice versa.
 * 2. Encapsulation: domain logic and invariants are not exposed to the presentation
 *    layer; consumers get a flat, serialization-friendly structure.
 *
 * fromDomain() is the only place that knows how to map from a domain Product to this
 * flat structure. All fields are strings or scalar types — no domain objects leak out.
 */
final readonly class ProductView
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public float $price,
        public string $createdAt,
        public int $stock,
    ) {
    }

    public static function fromDomain(Product $product): self
    {
        return new self(
            id: $product->getId() ?? throw new \LogicException('Product must have an id to create a view.'),
            name: $product->getName(),
            description: $product->getDescription(),
            price: $product->getPrice(),
            createdAt: $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
            stock: $product->getStock(),
        );
    }
}
