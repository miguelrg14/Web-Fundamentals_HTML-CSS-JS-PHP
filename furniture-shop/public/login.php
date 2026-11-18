<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

use Shop\UserRepository;

if (current_user_id() !== null) {
    redirect('products.php');
}

$email = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_post();
    $email = (string) ($_POST['email'] ?? '');
    $userId = (new UserRepository(db()))->authenticate($email, (string) ($_POST['password'] ?? ''));

    if ($userId !== null) {
        log_in($userId);
        redirect('products.php');
    }
    // Same message for an unknown email and a wrong password, so it doesn't reveal which emails exist.
    $error = 'Wrong email or password.';
    http_response_code(401);
}

page_start('Log in', 'login.jpg');
credentials_form('login.php', 'Log in', $email, $error, 'current-password');
?>
        <p class="switch">New here? <a href="register.php">Create an account</a></p>
<?php
page_end();
