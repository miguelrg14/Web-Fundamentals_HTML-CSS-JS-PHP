<?php

declare(strict_types=1);

namespace Shop\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shop\Csrf;

final class CsrfTest extends TestCase
{
    public function testTheTokenIsRandomAndKeptInTheSession(): void
    {
        $session = [];
        $token = Csrf::token($session);

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, Csrf::token($session));

        $otherSession = [];
        self::assertNotSame($token, Csrf::token($otherSession));
    }

    public function testOnlyTheSessionTokenIsValid(): void
    {
        $session = [];
        $token = Csrf::token($session);

        self::assertTrue(Csrf::isValid($session, $token));
        self::assertFalse(Csrf::isValid($session, strrev($token)));
        self::assertFalse(Csrf::isValid($session, ''));
        self::assertFalse(Csrf::isValid($session, null));
        self::assertFalse(Csrf::isValid($session, [$token]));
    }

    public function testNothingIsValidBeforeATokenExists(): void
    {
        self::assertFalse(Csrf::isValid([], ''));
        self::assertFalse(Csrf::isValid([], 'anything'));
    }
}
