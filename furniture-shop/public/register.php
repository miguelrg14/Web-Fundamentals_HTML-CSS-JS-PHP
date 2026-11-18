<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

use Shop\UserRepository;
use Shop\ValidationException;

if (current_user_id() !== null) {
    redirect('products.php');
}

$email = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_post();
    $email = (string) ($_POST['email'] ?? '');
    try {
        $userId = (new UserRepository(db()))->register($email, (string) ($_POST['password'] ?? ''));
        log_in($userId);
        flash('Welcome! Your account is ready.');
        redirect('products.php');
    } catch (ValidationException $exception) {
        $error = $exception->getMessage();
        http_response_code(422);
    }
}

page_start('Sign up', 'register.jpg');
credentials_form('register.php', 'Create account', $email, $error, 'new-password');
?>
        <p class="switch">Already have an account? <a href="login.php">Log in</a></p>
<?php
page_end();
