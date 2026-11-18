<?php

declare(strict_types=1);

namespace Shop\Tests\Integration;

use PDOException;
use Shop\Cart;
use Shop\CartLine;
use Shop\OrderRepository;
use Shop\Product;
use Shop\ProductRepository;
use Shop\UserRepository;
use Shop\ValidationException;

final class OrderRepositoryTest extends DatabaseTestCase
{
    private ProductRepository $products;
    private OrderRepository $orders;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = new ProductRepository($this->db);
        $this->orders = new OrderRepository($this->db);
        $this->userId = (new UserRepository($this->db))->register('ana@example.com', 'correct horse');
    }

    public function testTheCatalogComesFromTheDatabase(): void
    {
        $all = $this->products->all();

        self::assertCount(2, $all);
        self::assertSame('White chaise longue', $all[0]->name);
        self::assertSame(34900, $all[0]->priceCents);
        self::assertNull($this->products->find(999));
    }

    public function testPlacingAnOrderSavesItWithItsLines(): void
    {
        $cart = Cart::fromArray([]);
        $cart->add($this->products, 1, 2);
        $cart->add($this->products, 2, 1);

        $orderId = $this->orders->place($this->userId, $cart->lines($this->products));
        $order = $this->orders->findForUser($orderId, $this->userId);

        self::assertNotNull($order);
        self::assertSame(88750, $order['total_cents']);
        self::assertSame([
            ['name' => 'Glass desk', 'quantity' => 1, 'unit_price_cents' => 18950],
            ['name' => 'White chaise longue', 'quantity' => 2, 'unit_price_cents' => 34900],
        ], $order['items']);
    }

    public function testOrdersKeepThePriceTheyWerePlacedAt(): void
    {
        $orderId = $this->orders->place($this->userId, [new CartLine($this->products->find(1), 1)]);
        $this->db->exec('UPDATE products SET price_cents = 99900 WHERE id = 1');

        self::assertSame(34900, $this->orders->findForUser($orderId, $this->userId)['total_cents']);
    }

    public function testAnEmptyCartCantBeOrdered(): void
    {
        $this->expectException(ValidationException::class);
        $this->orders->place($this->userId, []);
    }

    public function testAFailedOrderLeavesNothingBehind(): void
    {
        $missing = new Product(999, 'Ghost chair', 100, 'none.jpg'); // breaks the foreign key
        $lines = [new CartLine($this->products->find(1), 1), new CartLine($missing, 1)];

        try {
            $this->orders->place($this->userId, $lines);
            self::fail('The order should have failed.');
        } catch (PDOException) {
            self::assertSame(0, $this->rowsIn('orders'));
            self::assertSame(0, $this->rowsIn('order_items'));
            self::assertFalse($this->db->inTransaction());
        }
    }

    public function testUsersOnlySeeTheirOwnOrders(): void
    {
        $orderId = $this->orders->place($this->userId, [new CartLine($this->products->find(1), 1)]);
        $otherUser = (new UserRepository($this->db))->register('ben@example.com', 'correct horse');

        self::assertNull($this->orders->findForUser($orderId, $otherUser));
        self::assertNull($this->orders->findForUser(999, $this->userId));
    }

    public function testTheDatabaseRejectsImpossibleQuantities(): void
    {
        $this->expectException(PDOException::class);
        $orderId = $this->orders->place($this->userId, [new CartLine($this->products->find(1), 1)]);
        $this->db->exec("INSERT INTO order_items VALUES ({$orderId}, 2, 0, 18950)");
    }
}
