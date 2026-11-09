<?php

namespace App\Providers;

use App\Domain\Access\PermissionRegistry;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Every permission key (for example "users.manage") is checked through the
        // Gate, so routes, controllers and views all use the same server-side rule.
        // Returning null (not false) lets other policies still run.
        Gate::before(function (?User $user, string $ability) {
            if ($user === null || ! PermissionRegistry::has($ability)) {
                return null;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}
