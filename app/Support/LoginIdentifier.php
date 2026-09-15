<?php

namespace App\Support;

use Illuminate\Support\Str;

class LoginIdentifier
{
    public static function normalize(string $identifier): string
    {
        return Str::squish($identifier);
    }

    public static function key(string $identifier): string
    {
        return Str::lower(self::normalize($identifier));
    }

    public static function isEmail(string $identifier): bool
    {
        return filter_var(self::normalize($identifier), FILTER_VALIDATE_EMAIL) !== false;
    }
}
