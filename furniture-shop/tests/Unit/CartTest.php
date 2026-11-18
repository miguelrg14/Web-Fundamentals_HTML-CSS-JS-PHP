<?php

declare(strict_types=1);

namespace Shop\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shop\Cart;
use Shop\Product;
use Shop\ValidationException;

final class CartTest extends TestCase
{
    private InMemoryCatalog $catalog;

    protected function setUp(): void
    {
        $this->catalog = new InMemoryCatalog(
            new Product(1, 'Chaise longue', 34900, 'couch.jpg'),
            new Product(2, 'Desk', 18950, 'desk.jpg'),
        );
    }

    public function testANewCartIsEmpty(): void
    {
        $cart = Cart::fromArray(null);

        self::assertTrue($cart->isEmpty());
        self::assertSame([], $cart->lines($this->catalog));
    }

    public function testAddingAProductStoresOnlyItsIdAndQuantity(): void
    {
        $cart = Cart::fromArray([]);
        $cart->add($this->catalog, 1, 2);

        self::assertSame([1 => 2], $cart->toArray());
    }

    public function testAddingTheSameProductAgainIncreasesTheQuantity(): void
    {
        $cart = Cart::fromArray([]);
        $cart->add($this->catalog, 1, 2);
        $cart->add($this->catalog, 1, 3);

        self::assertSame([1 => 5], $cart->toArray());
    }

    public function testTheQuantityNeverGoesAboveTheMaximum(): void
    {
        $cart = Cart::fromArray([]);
        $cart->add($this->catalog, 1, Cart::MAX_QUANTITY);
        $cart->add($this->catalog, 1, 1);

        self::assertSame([1 => Cart::MAX_QUANTITY], $cart->toArray());
    }

    /** @return iterable<string, array{int}> */
    public static function invalidQuantities(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
        yield 'too many' => [Cart::MAX_QUANTITY + 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidQuantities')]
    public function testInvalidQuantitiesAreRefused(int $quantity): void
    {
        $cart = Cart::fromArray([]);

        $this->expectException(ValidationException::class);
        $cart->add($this->catalog, 1, $quantity);
    }

    public function testUnknownProductsAreRefused(): void
    {
        $cart = Cart::fromArray([]);

        $this->expectException(ValidationException::class);
        $cart->add($this->catalog, 999, 1);
    }

    public function testLinesAndTotalUseTheCatalogPrices(): void
    {
        $cart = Cart::fromArray([]);
        $cart->add($this->catalog, 1, 2);
        $cart->add($this->catalog, 2, 1);

        $lines = $cart->lines($this->catalog);

        self::assertCount(2, $lines);
        self::assertSame('Chaise longue', $lines[0]->product->name);
        self::assertSame(69800, $lines[0]->subtotalCents());
        self::assertSame(88750, Cart::totalCents($lines));
    }

    public function testRemovingAProduct(): void
    {
        $cart = Cart::fromArray([1 => 1, 2 => 4]);
        $cart->remove(1);
        $cart->remove(42); // removing something that isn't there is harmless

        self::assertSame([2 => 4], $cart->toArray());
    }

    public function testProductsDeletedFromTheCatalogDisappearFromTheLines(): void
    {
        $cart = Cart::fromArray([1 => 1, 2 => 1]);
        $this->catalog->delete(2);

        self::assertCount(1, $cart->lines($this->catalog));
    }

    public function testMalformedSessionDataIsDropped(): void
    {
        $cart = Cart::fromArray([1 => 2, 'x' => 1, 3 => 'many', -4 => 1, 5 => 0, 6 => 500, 7 => 1.5]);

        self::assertSame([1 => 2, 6 => Cart::MAX_QUANTITY], $cart->toArray());
    }
}
