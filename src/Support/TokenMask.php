<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Support;

final class TokenMask
{
    public static function mask(string $token): string
    {
        if (strlen($token) <= 16) {
            return $token;
        }

        return substr($token, 0, 8).'…'.substr($token, -8);
    }
}
