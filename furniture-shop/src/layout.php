<?php

declare(strict_types=1);

/** Opens the page: <head>, the top bar and a full-width picture with the page title on it. */
function page_start(string $title, string $heroImage): void
{
    $loggedIn = current_user_id() !== null;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Furniture Shop</title>
    <link rel="icon" href="images/favicon.png">
    <link rel="stylesheet" href="css/shop.css">
</head>
<body>
    <header>
        <nav aria-label="Main">
            <span class="brand">Furniture Shop</span>
            <ul>
<?php if ($loggedIn): ?>
                <li><a href="products.php" title="Shop"><img src="images/icons/cart.png" alt="Shop" width="100" height="100"></a></li>
                <li>
                    <form action="logout.php" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" title="Log out"><img src="images/icons/logout.png" alt="Log out" width="100" height="100"></button>
                    </form>
                </li>
<?php else: ?>
                <li><a href="login.php" title="Log in"><img src="images/icons/login.png" alt="Log in" width="100" height="100"></a></li>
                <li><a href="register.php" title="Sign up"><img src="images/icons/register.png" alt="Sign up" width="100" height="100"></a></li>
<?php endif ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero">
            <h1><?= e($title) ?></h1>
            <img src="images/<?= e($heroImage) ?>" alt="">
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

/** The email + password form shared by the login and sign-up pages. */
function credentials_form(string $action, string $button, string $email, ?string $error, string $passwordAutocomplete): void
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
            <button type="submit"><?= e($button) ?></button>
        </form>
<?php
}
