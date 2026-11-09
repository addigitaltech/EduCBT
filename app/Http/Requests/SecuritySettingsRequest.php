<?php

namespace App\Http\Requests;

use App\Domain\Settings\SettingDefinitions;
use Illuminate\Foundation\Http\FormRequest;

class SecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $all = SettingDefinitions::all();

        return [
            'max_login_attempts' => ['required', 'integer', 'between:'.$all['security.max_login_attempts']['min'].','.$all['security.max_login_attempts']['max']],
            'lockout_minutes' => ['required', 'integer', 'between:'.$all['security.lockout_minutes']['min'].','.$all['security.lockout_minutes']['max']],
            'password_min_length' => ['required', 'integer', 'between:'.$all['security.password_min_length']['min'].','.$all['security.password_min_length']['max']],
        ];
    }
}
