<?php

declare(strict_types=1);

namespace Shop;

final class View
{
    /** Escapes text for HTML, so data shown on the page can't inject markup or scripts (XSS). */
    public static function e(string|int $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** 34900 => "349.00 €" */
    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',') . ' €';
    }
}
