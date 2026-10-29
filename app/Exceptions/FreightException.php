<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule of the freight domain was violated. The message is always
 * Persian and safe to show to the end user.
 */
class FreightException extends RuntimeException
{
    public static function because(string $message): static
    {
        return new static($message);
    }
}
