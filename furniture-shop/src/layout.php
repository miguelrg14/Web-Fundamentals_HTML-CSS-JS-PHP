<?php

declare(strict_types=1);

/**
 * Opens the page: <head>, the top bar and the entrance, a picture that fills the first screen
 * with the page title in the middle.
 *
 * @param callable|null $heroContent prints more content centred under the title (the login form)
 * @param string|null   $next        id of the section the "scroll down" arrow leads to
 */
function page_start(string $title, string $heroImage, ?callable $heroContent = null, ?string $next = null): void
{
    $loggedIn = current_user_id() !== null;
    $cartCount = array_sum(cart()->toArray());
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Furniture Shop</title>
    <link rel="icon" href="images/favicon.png">
    <link rel="stylesheet" href="css/shop.css?v=2">
</head>
<body>
    <header>
        <nav aria-label="Main">
            <a class="brand" href="index.php">Furniture Shop</a>
            <ul>
<?php if ($loggedIn): ?>
                <li><a href="products.php#catalog"><?= icon('sofa') ?><span class="label">Shop</span></a></li>
                <li>
                    <a href="products.php#cart"><?= icon('cart') ?><span class="label">Cart</span><?php if ($cartCount > 0): ?>
                        <span class="badge"><?= e($cartCount) ?><span class="visually-hidden"> items</span></span><?php endif ?></a>
                </li>
                <li>
                    <form action="logout.php" method="post">
                        <?= csrf_field() ?>
                        <button class="nav-button" type="submit"><?= icon('logout') ?><span class="label">Log out</span></button>
                    </form>
                </li>
<?php else: ?>
                <li><a href="login.php"><?= icon('user') ?><span class="label">Log in</span></a></li>
                <li><a class="nav-button" href="register.php">Sign up</a></li>
<?php endif ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero">
            <img src="images/<?= e($heroImage) ?>" alt="" width="1600" height="1067">
            <h1><?= e($title) ?></h1>
<?php if ($heroContent !== null) {
        $heroContent();
    } ?>
<?php if ($next !== null): ?>
            <a class="scroll-hint" href="#<?= e($next) ?>" aria-label="Scroll down">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9l7 7 7-7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
<?php endif ?>
        </div>
<?php
    $message = flash();
    if ($message !== null): ?>
        <p class="notice" role="status"><?= e($message) ?></p>
<?php endif;
}

function page_end(): void
{
    ?>
    </main>

    <footer>
        <p><strong>Made by Miguel Rodríguez Gallego</strong></p>
        <p>Coursework for the Computer Science &amp; Video Game Engineering degree</p>
    </footer>
</body>
</html>
<?php
}

/**
 * The email + password form shared by the login and sign-up pages.
 *
 * @param array{0: string, 1: string, 2: string} $switch text, link text and address of the link to the other page
 */
function credentials_form(string $action, string $button, string $email, ?string $error, string $passwordAutocomplete, array $switch): void
{
    ?>
        <form class="card credentials" action="<?= e($action) ?>" method="post">
            <?= csrf_field() ?>
<?php if ($error !== null): ?>
            <p class="error" role="alert"><?= e($error) ?></p>
<?php endif ?>
            <label>Email
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required maxlength="254">
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="<?= e($passwordAutocomplete) ?>" required<?=
                    $passwordAutocomplete === 'new-password' ? ' minlength="' . Shop\UserRepository::MIN_PASSWORD_LENGTH . '"' : '' ?>>
            </label>
            <button class="button wide" type="submit"><?= e($button) ?></button>
            <p class="switch"><?= e($switch[0]) ?> <a href="<?= e($switch[2]) ?>"><?= e($switch[1]) ?></a></p>
        </form>
<?php
}

/** Small line icons drawn with SVG, so they stay sharp at any size and need no image files. */
function icon(string $name): string
{
    $paths = [
        'cart' => '<path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.4-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'sofa' => '<path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><path d="M2 13a2 2 0 0 1 4 0v2h12v-2a2 2 0 0 1 4 0v5H2z"/><path d="M5 18v2M19 18v2"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
}
