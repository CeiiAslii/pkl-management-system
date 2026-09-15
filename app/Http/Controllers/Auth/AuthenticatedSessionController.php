<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ResolveLoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\LoginRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ResolveLoginUser $resolveLoginUser, LoginRateLimiter $rateLimiter): RedirectResponse|Response|JsonResponse
    {
        $identifierKey = $rateLimiter->identifierKey($request->identifier(), $request->ip());
        $ipKey = $rateLimiter->ipKey($request->ip());

        if ($rateLimiter->tooManyAttempts($identifierKey, $ipKey)) {
            return $this->throttled($request, $rateLimiter->retryAfter($identifierKey, $ipKey));
        }

        $loginUser = $resolveLoginUser->handle($request->identifier());

        if ($loginUser === null || ! Auth::attemptWhen([
            'email' => $loginUser->email,
            'password' => $request->password(),
        ], fn (User $user): bool => $user->isActive())) {
            $rateLimiter->hit($identifierKey, $ipKey);

            throw ValidationException::withMessages([
                'identifier' => 'Nama/email atau kata sandi salah.',
            ]);
        }

        $rateLimiter->clearIdentifier($identifierKey);
        $request->session()->regenerate();
        $request->session()->put('auth_user_id', Auth::id());
        $request->session()->put('auth_version', Auth::user()->auth_version);

        return to_route('account');
    }

    private function throttled(LoginRequest $request, int $seconds): Response|JsonResponse
    {
        $headers = ['Retry-After' => (string) $seconds];
        $message = 'Terlalu banyak percobaan masuk. Untuk keamanan akun, coba lagi dalam '.$seconds.' detik.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return response()->view('auth.login', [
            'throttleSeconds' => $seconds,
            'throttledIdentifier' => $request->identifier(),
        ], 429, $headers);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
