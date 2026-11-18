<?php

declare(strict_types=1);

namespace Shop;

/** Where the cart looks up products and their real prices. */
interface ProductCatalog
{
    /** @return list<Product> */
    public function all(): array;

    public function find(int $id): ?Product;
}
