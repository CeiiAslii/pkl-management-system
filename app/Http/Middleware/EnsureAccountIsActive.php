<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $sessionUserId = $request->session()->get('auth_user_id');
            $sessionVersion = $request->session()->get('auth_version');

            if ($sessionUserId === null || (int) $sessionUserId !== (int) $user->getKey()) {
                $request->session()->put('auth_user_id', $user->getKey());
                $request->session()->put('auth_version', $user->auth_version);
            } elseif ((int) $sessionVersion !== (int) $user->auth_version) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return to_route('login')->with('status', 'Keamanan akun Anda telah diperbarui. Silakan masuk kembali.');
            }
        }

        if (! $user?->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your account must be approved and active.');
        }

        return $next($request);
    }
}
