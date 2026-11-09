<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Used for both creating a role and changing what a role allows. */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
        ];

        if ($this->isMethod('POST')) {
            $rules['name'] = ['required', 'string', 'min:3', 'max:120'];
            $rules['description'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
