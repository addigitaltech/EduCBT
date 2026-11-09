<?php

namespace App\Providers;

use App\Support\Profile\Profile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Loads only the routes and migrations that belong to the active profile.
 * Profile-specific code never loads in the other profile.
 */
class ProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $name = (string) config('profile.name');

        if (Profile::tryFrom($name) === null) {
            throw new RuntimeException(
                "Invalid APP_PROFILE \"{$name}\". Use \"central\" or \"school\"."
            );
        }
    }

    public function boot(): void
    {
        $profile = Profile::current();

        $this->loadMigrationsFrom([
            database_path('migrations/shared'),
            database_path('migrations/'.$profile->value),
        ]);

        // Session, CSRF and cookie middleware come from the "web" group.
        Route::middleware('web')->group(base_path('routes/shared.php'));
        Route::middleware('web')->group(base_path('routes/'.$profile->value.'.php'));
    }
}
