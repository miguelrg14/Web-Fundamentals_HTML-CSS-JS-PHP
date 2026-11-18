<?php

declare(strict_types=1);

namespace Shop\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Shop\Database;

/**
 * Base class for tests against a real MySQL database. Each test starts from a fresh copy of
 * database/schema.sql. Without DB_HOST in the environment these tests are skipped.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        if (getenv('DB_HOST') === false) {
            self::markTestSkipped('Set DB_HOST, DB_NAME, DB_USER and DB_PASSWORD to run the database tests.');
        }

        $this->db = Database::connect();
        $schema = (string) file_get_contents(__DIR__ . '/../../database/schema.sql');
        foreach (preg_split('/;\s*$/m', $schema) as $statement) {
            if (trim(preg_replace('/^\s*--.*$/m', '', $statement)) !== '') {
                $this->db->exec($statement);
            }
        }
    }

    protected function count(string $table): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
}
