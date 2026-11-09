<?php

namespace Database\Seeders;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Access\PermissionRegistry;
use App\Domain\Access\RoleCatalog;
use App\Support\Profile\Profile;
use Illuminate\Database\Seeder;

/**
 * Syncs permissions from the code registry and creates the built-in roles for
 * the active profile. Safe to run again after an update: it adds new
 * permissions, keeps locked roles at full access, and never overwrites changes
 * a school made to other built-in roles.
 */
class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $profile = Profile::current();

        foreach (PermissionRegistry::forProfile($profile) as $key => $definition) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['scope' => $definition['scope'], 'group' => $definition['group'], 'description' => $definition['description']],
            );
        }

        $ids = Permission::query()->pluck('id', 'key');

        foreach (RoleCatalog::for($profile) as $definition) {
            $role = Role::query()->firstOrCreate(
                ['scope' => $profile->value, 'slug' => $definition['slug']],
                ['name' => $definition['name'], 'description' => $definition['description'], 'is_system' => true],
            );

            if ($role->wasRecentlyCreated || $definition['locked']) {
                $permissionIds = $definition['permissions'] === '*'
                    ? $ids->values()->all()
                    : collect($definition['permissions'])->map(fn (string $key) => $ids[$key])->values()->all();

                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
