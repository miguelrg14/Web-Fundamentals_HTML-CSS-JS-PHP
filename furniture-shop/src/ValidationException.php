<?php

declare(strict_types=1);

namespace Shop;

/** A problem with what the user typed; its message is safe to show on the page. */
final class ValidationException extends \RuntimeException
{
}
