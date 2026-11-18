<?php

declare(strict_types=1);

// Shared setup for every page: class loading, security headers, the session and small helpers.
// This folder is outside public/, so the web server never serves these files directly.

use Shop\Cart;
use Shop\Csrf;
use Shop\Database;
use Shop\View;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Shop\\')) {
        require __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen('Shop\\'))) . '.php';
    }
});

// Show errors in the log, never on the page, where they could reveal internals.
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; form-action 'self'; frame-ancestors 'none'");

session_start([
    'cookie_httponly' => true,   // JavaScript can't read the session cookie
    'cookie_samesite' => 'Lax',  // the cookie isn't sent on cross-site form posts
    'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
    'use_strict_mode' => true,   // reject session ids the server didn't create
]);

function db(): PDO
{
    static $pdo = null;
    return $pdo ??= Database::connect();
}

function e(string|int $text): string
{
    return View::e($text);
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303); // 303: the browser follows with a GET
    exit;
}

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    return is_int($id) ? $id : null;
}

/** Pages that need a logged-in user call this first. */
function require_login(): int
{
    return current_user_id() ?? redirect('login.php');
}

function log_in(int $userId): void
{
    session_regenerate_id(true); // a new session id on login prevents session fixation
    $_SESSION['user_id'] = $userId;
}

function log_out(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Csrf::token($_SESSION)) . '">';
}

/** Every POST must carry the form's CSRF token; anything else is rejected. */
function require_valid_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !Csrf::isValid($_SESSION, $_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request. Please go back, reload the page and try again.');
    }
}

function cart(): Cart
{
    return Cart::fromArray($_SESSION['cart'] ?? []);
}

function save_cart(Cart $cart): void
{
    $_SESSION['cart'] = $cart->toArray();
}

/** A message shown once on the next page, after a redirect. */
function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $saved = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_string($saved) ? $saved : null;
}

function int_input(array $source, string $key): ?int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT);
    return $value === false ? null : $value;
}
