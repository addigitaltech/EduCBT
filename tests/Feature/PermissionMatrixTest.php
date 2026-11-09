<?php

use App\Domain\Access\PermissionRegistry;
use Illuminate\Support\Facades\Route;

/*
 * Every protected route must (a) refuse guests, (b) refuse signed-in people who lack
 * the permission, and (c) allow people who hold it. The route list is read from the
 * application itself, so a new route cannot be added without a permission decision.
 */

it('requires a permission for every settings and system route', function (): void {
    $guarded = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'can:')));

    expect($guarded->count())->toBeGreaterThan(10);

    $unguarded = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'settings') || str_starts_with($route->uri(), 'system'))
        ->reject(fn ($route) => $route->getName() === 'settings.index')
        ->filter(fn ($route) => ! collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'can:')));

    expect($unguarded->map(fn ($route) => $route->uri())->all())->toBe([]);
});

it('uses only permission keys that exist in the registry', function (): void {
    $keys = collect(Route::getRoutes()->getRoutes())
        ->flatMap(fn ($route) => $route->gatherMiddleware())
        ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))
        ->map(fn ($m) => substr($m, 4))
        ->unique();

    foreach ($keys as $key) {
        expect(PermissionRegistry::has($key))->toBeTrue();
    }
});

dataset('read pages', [
    'users' => ['/settings/users', 'users.view'],
    'roles' => ['/settings/roles', 'roles.view'],
    'security' => ['/settings/security', 'settings.view'],
    'appearance' => ['/settings/appearance', 'settings.view'],
    'audit log' => ['/settings/audit-log', 'audit.view'],
    'system health' => ['/system/health', 'system.health.view'],
]);

it('sends guests to sign in', function (string $uri, string $permission): void {
    $this->get($uri)->assertRedirect('/login');
})->with('read pages');

it('forbids people without the permission', function (string $uri, string $permission): void {
    $others = array_values(array_diff(allPermissionKeys(), [$permission]));

    $this->actingAs(userWith($others))->get($uri)->assertForbidden();
})->with('read pages');

it('allows people with the permission', function (string $uri, string $permission): void {
    $this->actingAs(userWith([$permission]))->get($uri)->assertOk();
})->with('read pages');

dataset('write actions', [
    'add user' => ['post', '/settings/users', 'users.manage'],
    'create role' => ['post', '/settings/roles', 'roles.manage'],
    'save security' => ['put', '/settings/security', 'settings.manage'],
    'save appearance' => ['put', '/settings/appearance', 'settings.manage'],
]);

it('forbids changes without the manage permission, even with the view permission', function (string $method, string $uri, string $permission): void {
    $view = str_replace('manage', 'view', $permission);

    $this->actingAs(userWith([$view]))->{$method}($uri, [])->assertForbidden();
})->with('write actions');

it('opens the first settings tab a person may use', function (): void {
    $this->actingAs(userWith(['audit.view']))->get('/settings')->assertRedirect('/settings/audit-log');
    $this->actingAs(userWith([]))->get('/settings')->assertForbidden();
});

it('gives the administrator role every permission', function (): void {
    $admin = adminUser();

    expect($admin->permissionKeys())->toEqualCanonicalizing(allPermissionKeys());
});

it('hides actions a person may not use', function (): void {
    $this->actingAs(userWith(['users.view']))->get('/settings/users')
        ->assertOk()
        ->assertDontSee('Add user');

    $this->actingAs(userWith(['users.view', 'users.manage']))->get('/settings/users')
        ->assertOk()
        ->assertSee('Add user');
});
