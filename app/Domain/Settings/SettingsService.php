<?php

namespace App\Domain\Settings;

use App\Domain\Identity\Models\User;
use App\Domain\Settings\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;

final class SettingsService
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $id = $group.'.'.$key;

        if (array_key_exists($id, $this->memo)) {
            return $this->memo[$id];
        }

        $row = AppSetting::query()->where('group', $group)->where('key', $key)->first();

        if ($row === null || $row->value === null) {
            return $this->memo[$id] = $default ?? SettingDefinitions::default($group, $key);
        }

        $raw = $row->is_encrypted ? Crypt::decryptString($row->value) : $row->value;

        return $this->memo[$id] = json_decode($raw, true);
    }

    public function set(string $group, string $key, mixed $value, bool $encrypt = false, ?User $by = null): void
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);

        AppSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $encrypt ? Crypt::encryptString($encoded) : $encoded,
                'is_encrypted' => $encrypt,
                'updated_by' => $by?->getKey(),
            ],
        );

        unset($this->memo[$group.'.'.$key]);
    }

    public function forgetMemo(): void
    {
        $this->memo = [];
    }
}
