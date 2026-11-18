<?php

declare(strict_types=1);

namespace Shop;

/**
 * Shopping cart kept in the session. It only stores product ids and quantities: names and
 * prices are always read from the database, so a visitor can't change what they pay by
 * editing the page.
 */
final class Cart
{
    public const MAX_QUANTITY = 99;

    /** @param array<int, int> $items product id => quantity */
    private function __construct(private array $items)
    {
    }

    /** Rebuilds the cart from what was saved in the session, dropping anything malformed. */
    public static function fromArray(mixed $saved): self
    {
        $items = [];
        if (is_array($saved)) {
            foreach ($saved as $id => $quantity) {
                if (is_int($id) && $id > 0 && is_int($quantity) && $quantity >= 1) {
                    $items[$id] = min($quantity, self::MAX_QUANTITY);
                }
            }
        }
        return new self($items);
    }

    /** @return array<int, int> */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Adds a quantity of a product; adding the same product again increases its quantity.
     *
     * @throws ValidationException if the product does not exist or the quantity is not 1-99.
     */
    public function add(ProductCatalog $catalog, int $productId, int $quantity): void
    {
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new ValidationException('The quantity must be between 1 and ' . self::MAX_QUANTITY . '.');
        }
        if ($catalog->find($productId) === null) {
            throw new ValidationException('That product does not exist.');
        }
        $this->items[$productId] = min(($this->items[$productId] ?? 0) + $quantity, self::MAX_QUANTITY);
    }

    public function remove(int $productId): void
    {
        unset($this->items[$productId]);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * The cart's contents with current prices. Products that no longer exist are left out.
     *
     * @return list<CartLine>
     */
    public function lines(ProductCatalog $catalog): array
    {
        $lines = [];
        foreach ($this->items as $id => $quantity) {
            $product = $catalog->find($id);
            if ($product !== null) {
                $lines[] = new CartLine($product, $quantity);
            }
        }
        return $lines;
    }

    /** @param list<CartLine> $lines */
    public static function totalCents(array $lines): int
    {
        return array_sum(array_map(static fn (CartLine $line): int => $line->subtotalCents(), $lines));
    }
}
