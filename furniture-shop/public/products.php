<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

use Shop\Cart;
use Shop\OrderRepository;
use Shop\ProductRepository;
use Shop\ValidationException;
use Shop\View;

$userId = require_login();
$catalog = new ProductRepository(db());

// Every change is a POST followed by a redirect (Post/Redirect/Get),
// so reloading the page never adds a product or places an order twice.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_post();
    $cart = cart();
    $back = 'catalog'; // where the page scrolls to after the change
    $productId = int_input($_POST, 'product_id') ?? 0;

    try {
        switch ($_POST['action'] ?? '') {
            case 'add':
                $cart->add($catalog, $productId, int_input($_POST, 'quantity') ?? 0);
                flash('Added to your cart.');
                break;
            case 'remove':
                $cart->remove($productId);
                $back = 'cart';
                break;
            case 'checkout':
                $back = 'cart';
                // Prices come from the database here too, never from the form.
                $orderId = (new OrderRepository(db()))->place($userId, $cart->lines($catalog));
                save_cart(Cart::fromArray([]));
                redirect('thanks.php?order=' . $orderId);
            default:
                http_response_code(400);
                exit('Unknown action.');
        }
    } catch (ValidationException $exception) {
        flash($exception->getMessage());
    }

    save_cart($cart);
    redirect('products.php#' . $back);
}

$lines = cart()->lines($catalog);

page_start('Products', 'products-hero.jpg', next: 'catalog');
?>
        <div class="shop">
            <section id="catalog" class="catalog" aria-labelledby="catalog-title">
                <h2 id="catalog-title">Our furniture</h2>
                <ul class="product-grid">
<?php foreach ($catalog->all() as $product): ?>
                    <li class="card product">
                        <img src="<?= e($product->image) ?>" alt="" width="900" height="600">
                        <div class="product-body">
                            <h3><?= e($product->name) ?></h3>
                            <p class="price"><?= e(View::money($product->priceCents)) ?></p>
                            <form action="products.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= e($product->id) ?>">
                                <label>
                                    <span class="visually-hidden">Quantity of <?= e($product->name) ?></span>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= Cart::MAX_QUANTITY ?>" required>
                                </label>
                                <button class="button" type="submit"><?= icon('cart') ?>Add to cart</button>
                            </form>
                        </div>
                    </li>
<?php endforeach ?>
                </ul>
            </section>

            <aside id="cart" class="card cart" aria-labelledby="cart-title">
                <h2 id="cart-title">Your cart</h2>
<?php if ($lines === []): ?>
                <p class="empty">Your cart is empty.</p>
<?php else: ?>
                <ul class="cart-lines">
<?php foreach ($lines as $line): ?>
                    <li>
                        <div>
                            <strong><?= e($line->product->name) ?></strong>
                            <span class="muted"><?= e($line->quantity) ?> × <?= e(View::money($line->product->priceCents)) ?></span>
                        </div>
                        <span class="subtotal"><?= e(View::money($line->subtotalCents())) ?></span>
                        <form action="products.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= e($line->product->id) ?>">
                            <button class="icon-button" type="submit" title="Remove">
                                <?= icon('trash') ?><span class="visually-hidden">Remove <?= e($line->product->name) ?></span>
                            </button>
                        </form>
                    </li>
<?php endforeach ?>
                </ul>
                <p class="total"><span>Total</span><strong><?= e(View::money(Cart::totalCents($lines))) ?></strong></p>
                <form action="products.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="checkout">
                    <button class="button wide" type="submit">Checkout</button>
                </form>
<?php endif ?>
            </aside>
        </div>
<?php
page_end();
