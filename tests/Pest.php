<?php

use App\Domain\Access\Models\Role;
use App\Domain\Access\PermissionRegistry;
use App\Domain\Identity\Models\User;
use App\Domain\System\Models\SystemIdentity;
use App\Support\Profile\Profile;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test setup
|--------------------------------------------------------------------------
| Feature tests run against an installed application (identity row plus
| built-in roles). The same suite runs for each profile: set APP_PROFILE=central
| or APP_PROFILE=school in the environment (CI runs both).
*/

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->withoutVite();
        SystemIdentity::install(Profile::current());
        (new AccessSeeder)->run();
    })
    ->in('Feature');

uses(TestCase::class)->in('Unit', 'Architecture');

/** The profile's administrator account (full access). */
function adminUser(): User
{
    $user = User::factory()->create();
    $slug = \App\Domain\Access\RoleCatalog::administratorSlug(Profile::current());
    $role = Role::query()->where('scope', Profile::current()->value)->where('slug', $slug)->firstOrFail();
    $user->roles()->attach($role->id, ['assigned_by' => null, 'created_at' => now()]);

    return $user;
}

/**
 * A user whose only permissions are the ones listed (through a throw-away custom role).
 *
 * @param  list<string>  $keys
 */
function userWith(array $keys): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'scope' => Profile::current()->value,
        'slug' => 'test_'.uniqid(),
        'name' => 'Test role',
        'is_system' => false,
    ]);
    $role->permissions()->sync(\App\Domain\Access\Models\Permission::query()->whereIn('key', $keys)->pluck('id')->all());
    $user->roles()->attach($role->id, ['assigned_by' => null, 'created_at' => now()]);

    return $user;
}

/** @return list<string> */
function allPermissionKeys(): array
{
    return array_keys(PermissionRegistry::forProfile(Profile::current()));
}
