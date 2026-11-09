<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Middleware\EnsureInstalled;
use Illuminate\Support\Facades\Route;

// Routes shared by both profiles. Profile-specific routes live in routes/central.php
// and routes/school.php.

Route::get('/health', HealthController::class)
    ->withoutMiddleware([EnsureInstalled::class])
    ->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::view('/help/credentials', 'auth.help')->name('credentials.help');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function (): void {
        Route::get('/system/health', SystemHealthController::class)
            ->middleware('can:system.health.view')
            ->name('system.health');

        Route::prefix('settings')->group(function (): void {
            Route::get('/', [SettingsController::class, 'index'])->name('settings.index');

            Route::get('/users', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->middleware('can:users.manage')->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->middleware('can:users.manage')->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.view')->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('can:users.manage')->name('users.update');
            Route::post('/users/{user}/status', [UserController::class, 'status'])->middleware('can:users.manage')->name('users.status');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('can:users.manage')->name('users.reset-password');

            Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->middleware('can:roles.manage')->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('can:roles.manage')->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.view')->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.manage')->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.manage')->name('roles.destroy');

            Route::get('/security', [SettingsController::class, 'security'])->middleware('can:settings.view')->name('settings.security');
            Route::put('/security', [SettingsController::class, 'updateSecurity'])->middleware('can:settings.manage')->name('settings.security.update');
            Route::get('/appearance', [SettingsController::class, 'appearance'])->middleware('can:settings.view')->name('settings.appearance');
            Route::put('/appearance', [SettingsController::class, 'updateAppearance'])->middleware('can:settings.manage')->name('settings.appearance.update');

            Route::get('/audit-log', AuditLogController::class)->middleware('can:audit.view')->name('audit.index');
        });
    });
});
