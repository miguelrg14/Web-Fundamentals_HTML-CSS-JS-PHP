<?php

declare(strict_types=1);

namespace Shop;

use PDO;

/**
 * Opens the MySQL connection. The settings come from environment variables, so no password
 * is written in the code: DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD.
 */
final class Database
{
    public static function connect(): PDO
    {
        $host = self::env('DB_HOST', '127.0.0.1');
        $port = self::env('DB_PORT', '3306');
        $name = self::env('DB_NAME', 'furniture_shop');

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            self::env('DB_USER', 'shop'),
            self::env('DB_PASSWORD', ''),
            [
                // Errors throw exceptions instead of failing silently.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Real prepared statements on the server: values never become part of the SQL text.
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
