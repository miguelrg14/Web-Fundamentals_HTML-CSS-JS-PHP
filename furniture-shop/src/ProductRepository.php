<?php

declare(strict_types=1);

namespace Shop;

use PDO;

final class ProductRepository implements ProductCatalog
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        $rows = $this->db->query('SELECT id, name, price_cents, image FROM products ORDER BY id')->fetchAll();
        return array_map(Product::fromRow(...), $rows);
    }

    public function find(int $id): ?Product
    {
        $statement = $this->db->prepare('SELECT id, name, price_cents, image FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : Product::fromRow($row);
    }
}
