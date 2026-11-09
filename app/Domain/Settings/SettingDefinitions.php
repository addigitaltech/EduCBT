<?php

namespace App\Domain\Settings;

/**
 * Every known setting with its default and validation limits. Nothing a school
 * might reasonably change is hard-coded elsewhere in the application.
 */
final class SettingDefinitions
{
    /** Accent colours that keep white text readable. */
    public const ACCENTS = [
        'navy' => 'Navy',
        'teal' => 'Teal',
        'green' => 'Green',
        'indigo' => 'Indigo',
        'maroon' => 'Maroon',
    ];

    /**
     * @return array<string, array{type: string, default: mixed, min?: int, max?: int}>
     */
    public static function all(): array
    {
        return [
            'security.max_login_attempts' => ['type' => 'int', 'default' => 5, 'min' => 3, 'max' => 20],
            'security.lockout_minutes' => ['type' => 'int', 'default' => 15, 'min' => 1, 'max' => 1440],
            'security.password_min_length' => ['type' => 'int', 'default' => 10, 'min' => 8, 'max' => 64],
            'appearance.display_name' => ['type' => 'string', 'default' => null],
            'appearance.accent' => ['type' => 'string', 'default' => 'navy'],
        ];
    }

    public static function default(string $group, string $key): mixed
    {
        return self::all()[$group.'.'.$key]['default'] ?? null;
    }
}
