<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS_PER_MINUTE = 5;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login_id.required' => 'Enter your sign-in ID.',
            'password.required' => 'Enter your password.',
        ];
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login_id')).'|'.$this->ip());
    }

    /** Limits guesses per account and address, whether or not the account exists. */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS_PER_MINUTE)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login_id' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
        ]);
    }
}
