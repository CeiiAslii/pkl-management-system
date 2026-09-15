<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

final class LoginRateLimiter
{
    public function identifierKey(string $identifier, string $ip): string
    {
        return 'login:account:'.hash('sha256', LoginIdentifier::key($identifier)).'|'.$ip;
    }

    public function ipKey(string $ip): string
    {
        return 'login:ip:'.$ip;
    }

    public function tooManyAttempts(string $identifierKey, string $ipKey): bool
    {
        return RateLimiter::tooManyAttempts($identifierKey, config('auth.rate_limits.login.identifier_attempts'))
            || RateLimiter::tooManyAttempts($ipKey, config('auth.rate_limits.login.ip_attempts'));
    }

    public function retryAfter(string $identifierKey, string $ipKey): int
    {
        return max(1, RateLimiter::availableIn($identifierKey), RateLimiter::availableIn($ipKey));
    }

    public function hit(string $identifierKey, string $ipKey): void
    {
        RateLimiter::hit($identifierKey, config('auth.rate_limits.login.decay_seconds'));
        RateLimiter::hit($ipKey, config('auth.rate_limits.login.decay_seconds'));
    }

    public function clearIdentifier(string $identifierKey): void
    {
        RateLimiter::clear($identifierKey);
    }
}
