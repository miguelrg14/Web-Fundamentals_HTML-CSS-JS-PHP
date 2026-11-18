<?php

declare(strict_types=1);

namespace Shop;

use PDO;
use Throwable;

final class OrderRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Saves the order and its lines in one transaction: either everything is stored or nothing is.
     * Returns the new order's id.
     *
     * @param list<CartLine> $lines
     * @throws ValidationException if there is nothing to order.
     */
    public function place(int $userId, array $lines): int
    {
        if ($lines === []) {
            throw new ValidationException('Your cart is empty.');
        }

        $this->db->beginTransaction();
        try {
            $order = $this->db->prepare('INSERT INTO orders (user_id, total_cents) VALUES (:user, :total)');
            $order->execute(['user' => $userId, 'total' => Cart::totalCents($lines)]);
            $orderId = (int) $this->db->lastInsertId();

            $item = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price_cents)
                 VALUES (:order, :product, :quantity, :price)'
            );
            foreach ($lines as $line) {
                $item->execute([
                    'order' => $orderId,
                    'product' => $line->product->id,
                    'quantity' => $line->quantity,
                    'price' => $line->product->priceCents,
                ]);
            }

            $this->db->commit();
            return $orderId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /**
     * An order of this user, or null if it doesn't exist or belongs to someone else.
     *
     * @return array{id: int, total_cents: int, items: list<array{name: string, quantity: int, unit_price_cents: int}>}|null
     */
    public function findForUser(int $orderId, int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT id, total_cents FROM orders WHERE id = :id AND user_id = :user');
        $statement->execute(['id' => $orderId, 'user' => $userId]);
        $order = $statement->fetch();
        if ($order === false) {
            return null;
        }

        $items = $this->db->prepare(
            'SELECT p.name, i.quantity, i.unit_price_cents
             FROM order_items i JOIN products p ON p.id = i.product_id
             WHERE i.order_id = :id ORDER BY p.name'
        );
        $items->execute(['id' => $orderId]);

        return [
            'id' => (int) $order['id'],
            'total_cents' => (int) $order['total_cents'],
            'items' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'quantity' => (int) $row['quantity'],
                'unit_price_cents' => (int) $row['unit_price_cents'],
            ], $items->fetchAll()),
        ];
    }
}
