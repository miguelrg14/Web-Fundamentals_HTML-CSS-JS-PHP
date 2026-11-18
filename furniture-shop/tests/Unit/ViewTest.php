<?php

declare(strict_types=1);

namespace Shop\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shop\View;

final class ViewTest extends TestCase
{
    public function testHtmlIsEscaped(): void
    {
        self::assertSame(
            '&lt;script&gt;alert(&quot;hi&quot;)&lt;/script&gt; &amp; &apos;quotes&apos;',
            View::e('<script>alert("hi")</script> & \'quotes\''),
        );
        self::assertSame('42', View::e(42));
    }

    public function testInvalidUtf8DoesNotBlankTheText(): void
    {
        self::assertSame("abc\u{FFFD}", View::e("abc\xff"));
    }

    public function testMoneyIsShownInEurosWithTwoDecimals(): void
    {
        self::assertSame('349.00 €', View::money(34900));
        self::assertSame('0.05 €', View::money(5));
        self::assertSame('1,234.50 €', View::money(123450));
    }
}
