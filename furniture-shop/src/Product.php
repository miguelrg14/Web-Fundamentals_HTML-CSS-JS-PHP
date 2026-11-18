<?php

declare(strict_types=1);

namespace Shop;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $priceCents,
        public readonly string $image,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], (string) $row['name'], (int) $row['price_cents'], (string) $row['image']);
    }
}
