<?php

declare(strict_types=1);

namespace Shop\Tests\Unit;

use Shop\Product;
use Shop\ProductCatalog;

/** A product catalog without a database, for unit tests. */
final class InMemoryCatalog implements ProductCatalog
{
    /** @var array<int, Product> */
    private array $products = [];

    public function __construct(Product ...$products)
    {
        foreach ($products as $product) {
            $this->products[$product->id] = $product;
        }
    }

    public function all(): array
    {
        return array_values($this->products);
    }

    public function find(int $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    public function delete(int $id): void
    {
        unset($this->products[$id]);
    }
}
