<?php

namespace App\Http\Requests\Auth;

use App\Support\LoginIdentifier;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('identifier'))) {
            $this->merge(['identifier' => LoginIdentifier::normalize($this->input('identifier'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ];
    }

    public function identifier(): string
    {
        return $this->string('identifier')->toString();
    }

    public function password(): string
    {
        return $this->string('password')->toString();
    }
}
