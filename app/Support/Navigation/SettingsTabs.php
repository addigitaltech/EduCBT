<?php

namespace App\Support\Navigation;

use App\Domain\Identity\Models\User;

/**
 * The tabs of the Settings workspace. Settings is one sidebar entry; each
 * group is a tab, shown only to people who may use it.
 */
final class SettingsTabs
{
    /**
     * @return list<array{label: string, route: string, permission: string, patterns: list<string>}>
     */
    private static function all(): array
    {
        return [
            ['label' => 'Users', 'route' => 'users.index', 'permission' => 'users.view', 'patterns' => ['users.*']],
            ['label' => 'Roles', 'route' => 'roles.index', 'permission' => 'roles.view', 'patterns' => ['roles.*']],
            ['label' => 'Security', 'route' => 'settings.security', 'permission' => 'settings.view', 'patterns' => ['settings.security*']],
            ['label' => 'Appearance', 'route' => 'settings.appearance', 'permission' => 'settings.view', 'patterns' => ['settings.appearance*']],
            ['label' => 'Audit log', 'route' => 'audit.index', 'permission' => 'audit.view', 'patterns' => ['audit.*']],
        ];
    }

    /**
     * @return list<array{label: string, route: string, active: bool}>
     */
    public static function visible(User $user): array
    {
        $tabs = [];

        foreach (self::all() as $tab) {
            if ($user->can($tab['permission'])) {
                $tabs[] = [
                    'label' => $tab['label'],
                    'route' => $tab['route'],
                    'active' => request()->routeIs(...$tab['patterns']),
                ];
            }
        }

        return $tabs;
    }

    public static function firstRoute(User $user): ?string
    {
        return self::visible($user)[0]['route'] ?? null;
    }
}
