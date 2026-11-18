<?php

declare(strict_types=1);

namespace Shop\Tests\Integration;

use Shop\UserRepository;
use Shop\ValidationException;

final class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->db);
    }

    public function testAUserCanSignUpAndLogIn(): void
    {
        $id = $this->users->register('ana@example.com', 'correct horse');

        self::assertSame($id, $this->users->authenticate('ana@example.com', 'correct horse'));
    }

    public function testThePasswordIsStoredAsAHash(): void
    {
        $this->users->register('ana@example.com', 'correct horse');

        $hash = $this->db->query("SELECT password_hash FROM users")->fetchColumn();
        self::assertStringNotContainsString('correct horse', $hash);
        self::assertTrue(password_verify('correct horse', $hash));
    }

    public function testEmailsIgnoreCaseAndSurroundingSpaces(): void
    {
        $id = $this->users->register('  Ana@Example.COM ', 'correct horse');

        self::assertSame($id, $this->users->authenticate('ana@example.com', 'correct horse'));
    }

    public function testWrongPasswordsAndUnknownEmailsAreRefused(): void
    {
        $this->users->register('ana@example.com', 'correct horse');

        self::assertNull($this->users->authenticate('ana@example.com', 'wrong horse'));
        self::assertNull($this->users->authenticate('nobody@example.com', 'correct horse'));
    }

    public function testSqlInjectionIsJustAWrongEmail(): void
    {
        $this->users->register('ana@example.com', 'correct horse');

        self::assertNull($this->users->authenticate("' OR '1'='1' -- ", "' OR '1'='1"));
        self::assertSame(1, $this->count('users'));
    }

    public function testAnEmailCanOnlyBeUsedOnce(): void
    {
        $this->users->register('ana@example.com', 'correct horse');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already an account');
        $this->users->register('ANA@example.com', 'another password');
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidSignUps(): iterable
    {
        yield 'not an email' => ['ana', 'correct horse'];
        yield 'empty email' => ['', 'correct horse'];
        yield 'short password' => ['ana@example.com', 'short'];
        yield 'password over 72 bytes' => ['ana@example.com', str_repeat('a', 73)];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSignUps')]
    public function testInvalidSignUpsAreRefused(string $email, string $password): void
    {
        try {
            $this->users->register($email, $password);
            self::fail('The sign-up should have been refused.');
        } catch (ValidationException) {
            self::assertSame(0, $this->count('users'));
        }
    }

    public function testOldHashesAreUpgradedOnLogin(): void
    {
        $id = $this->users->register('ana@example.com', 'correct horse');
        $weak = password_hash('correct horse', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$weak, $id]);

        self::assertSame($id, $this->users->authenticate('ana@example.com', 'correct horse'));

        $hash = $this->db->query('SELECT password_hash FROM users')->fetchColumn();
        self::assertNotSame($weak, $hash);
        self::assertFalse(password_needs_rehash($hash, PASSWORD_DEFAULT));
    }
}
