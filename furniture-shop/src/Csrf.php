<?php

declare(strict_types=1);

namespace Shop;

/**
 * Protection against cross-site request forgery: every form carries a secret token stored in
 * the session, so another website can't make a logged-in visitor submit a form without knowing it.
 */
final class Csrf
{
    private const KEY = 'csrf_token';

    /** @param array<string, mixed> $session */
    public static function token(array &$session): string
    {
        if (!isset($session[self::KEY]) || !is_string($session[self::KEY])) {
            $session[self::KEY] = bin2hex(random_bytes(32));
        }
        return $session[self::KEY];
    }

    /** @param array<string, mixed> $session */
    public static function isValid(array $session, mixed $token): bool
    {
        return isset($session[self::KEY]) && is_string($session[self::KEY]) && is_string($token)
            && hash_equals($session[self::KEY], $token); // constant-time comparison
    }
}
