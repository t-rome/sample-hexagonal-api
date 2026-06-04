<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence;

use App\Product\Domain\Model\Product;

final class ProductMapper
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
