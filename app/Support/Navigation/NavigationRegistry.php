<?php

namespace App\Support\Navigation;

use App\Domain\Identity\Models\User;

/**
 * The short main navigation. Items appear only when they exist in this phase
 * and the person is allowed to use them. Later phases add their workspaces here.
 */
final class NavigationRegistry
{
    /** @return list<NavItem> */
    public static function for(User $user): array
    {
        $items = [new NavItem('Dashboard', 'dashboard', 'layout-dashboard')];

        if ($user->can('system.health.view')) {
            $items[] = new NavItem('System health', 'system.health', 'activity');
        }

        $settingsRoute = SettingsTabs::firstRoute($user);
        if ($settingsRoute !== null) {
            $items[] = new NavItem('Settings', $settingsRoute, 'settings', ['users.*', 'roles.*', 'settings.*', 'audit.*']);
        }

        return $items;
    }
}
