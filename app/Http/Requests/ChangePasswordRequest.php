<?php

namespace App\Http\Requests;

use App\Domain\Settings\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $min = (int) app(SettingsService::class)->get('security', 'password_min_length');

        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min($min)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password is not correct.',
            'password.confirmed' => 'The new password and its confirmation do not match.',
            'password.different' => 'Choose a password that is different from your current one.',
        ];
    }
}
