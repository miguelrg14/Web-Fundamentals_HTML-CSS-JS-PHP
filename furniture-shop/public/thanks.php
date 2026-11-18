<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

use Shop\OrderRepository;
use Shop\View;

$userId = require_login();
$orderId = int_input($_GET, 'order') ?? 0;

// Only the order's owner can see it: another user's order id gives "not found".
$order = (new OrderRepository(db()))->findForUser($orderId, $userId);
if ($order === null) {
    http_response_code(404);
}

page_start('Thanks for buying!', 'thanks.jpg');
?>
        <section class="card order" aria-labelledby="order-title">
<?php if ($order === null): ?>
            <h2 id="order-title">Order not found</h2>
            <p><a href="products.php">Back to the shop</a></p>
<?php else: ?>
            <h2 id="order-title">Order #<?= e($order['id']) ?></h2>
            <p>Your purchase is confirmed. You will receive the package soon.</p>
            <table>
                <thead>
                    <tr><th scope="col">Product</th><th scope="col">Quantity</th><th scope="col">Price</th></tr>
                </thead>
                <tbody>
<?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= e($item['quantity']) ?></td>
                        <td><?= e(View::money($item['unit_price_cents'])) ?></td>
                    </tr>
<?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr><th scope="row" colspan="2">Total</th><td><?= e(View::money($order['total_cents'])) ?></td></tr>
                </tfoot>
            </table>
            <p><a href="products.php">Keep shopping</a></p>
<?php endif ?>
        </section>
<?php
page_end();
