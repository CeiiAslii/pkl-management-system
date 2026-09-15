<?php

namespace App\Actions;

use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Validation\ValidationException;

class ResolveLoginUser
{
    public function handle(string $identifier): ?User
    {
        $identifier = LoginIdentifier::normalize($identifier);
        $lookupKey = LoginIdentifier::key($identifier);

        if (LoginIdentifier::isEmail($identifier)) {
            return User::query()->whereRaw('LOWER(email) = ?', [$lookupKey])->first();
        }

        $matches = User::query()
            ->where('normalized_name', $lookupKey)
            ->limit(2)
            ->get();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'identifier' => 'Nama tersebut digunakan oleh lebih dari satu akun. Silakan masuk menggunakan email.',
            ]);
        }

        return $matches->first();
    }
}
