<?php

namespace App\Http\Requests;

use App\Domain\Settings\SettingDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppearanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:80'],
            'accent' => ['required', Rule::in(array_keys(SettingDefinitions::ACCENTS))],
        ];
    }
}
