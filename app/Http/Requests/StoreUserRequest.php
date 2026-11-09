<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('login_id'))) {
            $this->merge(['login_id' => mb_strtolower(trim($this->input('login_id')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'login_id' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[a-z0-9._@\/-]+$/', Rule::unique('users', 'login_id')],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'login_id.regex' => 'Use only letters, numbers and these symbols: . _ @ / -',
            'login_id.unique' => 'This sign-in ID is already used.',
            'email.unique' => 'This email address is already used.',
        ];
    }
}
