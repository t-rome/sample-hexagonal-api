<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence;

use App\Product\Domain\Model\Product;

/**
 * Translates between the Product domain model and the Doctrine ORM record.
 *
 * The domain model (Product) is kept free of ORM annotations and persistence
 * concerns. This mapper acts as an anti-corruption layer between the two worlds:
 *
 * toDomain() — called when loading from the database; reconstructs a pure domain
 *              object via Product::reconstitute(), bypassing normal business rules
 *              since the data was already validated when it was first saved.
 *
 * toRecord() — called before persisting; maps domain state onto the ORM entity.
 *              Accepts an existing record to allow Doctrine's change-tracking to
 *              detect which columns actually changed (UPDATE instead of INSERT).
 */
final readonly class ProductMapper
{
    public function toDomain(ProductRecord $record): Product
    {
        return Product::reconstitute(
            id: $record->id ?? throw new \LogicException('ProductRecord must have an id.'),
            name: $record->name,
            description: $record->description,
            price: $record->price,
            createdAt: $record->createdAt,
            stock: $record->stock,
        );
    }

    public function toRecord(Product $product, ?ProductRecord $existing = null): ProductRecord
    {
        $record = $existing ?? new ProductRecord();
        $record->name = $product->getName();
        $record->description = $product->getDescription();
        $record->price = $product->getPrice();
        $record->createdAt = $product->getCreatedAt();
        $record->stock = $product->getStock();

        return $record;
    }
}
