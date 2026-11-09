<?php

namespace App\Domain\Access;

use App\Support\Profile\Profile;

/**
 * The single list of permissions. Permissions are defined in code and seeded to
 * the database; users can only assign them to roles, never invent new ones.
 * Later phases add their own keys here.
 */
final class PermissionRegistry
{
    /**
     * @return array<string, array{scope: string, group: string, description: string}>
     */
    public static function all(): array
    {
        return [
            'users.view' => ['scope' => 'shared', 'group' => 'Users', 'description' => 'See the list of users'],
            'users.manage' => ['scope' => 'shared', 'group' => 'Users', 'description' => 'Add users, change their roles and reset passwords'],
            'roles.view' => ['scope' => 'shared', 'group' => 'Roles', 'description' => 'See roles and what they allow'],
            'roles.manage' => ['scope' => 'shared', 'group' => 'Roles', 'description' => 'Create roles and change what they allow'],
            'settings.view' => ['scope' => 'shared', 'group' => 'Settings', 'description' => 'See security and appearance settings'],
            'settings.manage' => ['scope' => 'shared', 'group' => 'Settings', 'description' => 'Change security and appearance settings'],
            'audit.view' => ['scope' => 'shared', 'group' => 'Audit', 'description' => 'Read the audit log'],
            'system.health.view' => ['scope' => 'shared', 'group' => 'System', 'description' => 'See system health'],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * @return array<string, array{scope: string, group: string, description: string}>
     */
    public static function forProfile(Profile $profile): array
    {
        return array_filter(
            self::all(),
            fn (array $definition) => in_array($definition['scope'], ['shared', $profile->value], true),
        );
    }
}
