<?php

declare(strict_types=1);

namespace Shop;

use PDO;
use PDOException;

/** Sign-up and login. Passwords are stored as password_hash() hashes, never as text. */
final class UserRepository
{
    public const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 72; // bcrypt only uses the first 72 bytes
    private const DUPLICATE_KEY = '23000';
    // Hash of a random password, used to spend the same time when the email does not exist.
    private const DUMMY_HASH = '$2y$10$e/54QebQTbofQOmqxMSw0uUWCydrFn1wjOUKT66uSvdDz44LawwJq';

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Creates an account and returns its id.
     *
     * @throws ValidationException if the email or password is not valid, or the email is taken.
     */
    public function register(string $email, string $password): int
    {
        $email = self::normalizeEmail($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
            throw new ValidationException('Please enter a valid email address.');
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ValidationException('The password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters long.');
        }
        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            throw new ValidationException('The password must be at most ' . self::MAX_PASSWORD_LENGTH . ' bytes long.');
        }

        try {
            $statement = $this->db->prepare('INSERT INTO users (email, password_hash) VALUES (:email, :hash)');
            $statement->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $exception) {
            // The UNIQUE constraint decides, so two sign-ups at the same time can't both win.
            if ($exception->getCode() === self::DUPLICATE_KEY) {
                throw new ValidationException('There is already an account with that email.');
            }
            throw $exception;
        }
        return (int) $this->db->lastInsertId();
    }

    /** Returns the user's id if the email and password match, or null otherwise. */
    public function authenticate(string $email, string $password): ?int
    {
        $statement = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = :email');
        $statement->execute(['email' => self::normalizeEmail($email)]);
        $user = $statement->fetch();

        if ($user === false) {
            // Hash anyway so an unknown email takes as long as a wrong password,
            // and response times don't reveal which emails have an account.
            password_verify($password, self::DUMMY_HASH);
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Upgrade old hashes when PHP's default algorithm or cost changes.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
        }
        return (int) $user['id'];
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
