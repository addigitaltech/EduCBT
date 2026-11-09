<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Access\PermissionRegistry;
use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateRole
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<string>  $permissionKeys
     */
    public function handle(string $name, ?string $description, array $permissionKeys, User $actor): Role
    {
        $profile = Profile::current();
        $permissionKeys = array_values(array_unique($permissionKeys));

        if (array_diff($permissionKeys, array_keys(PermissionRegistry::forProfile($profile))) !== []) {
            throw ValidationException::withMessages(['permissions' => 'One or more permissions are not valid.']);
        }

        if (array_diff($permissionKeys, $actor->permissionKeys()) !== []) {
            throw ValidationException::withMessages([
                'permissions' => 'You can only give a role permissions that you have yourself.',
            ]);
        }

        $slug = Str::slug($name, '_');

        if ($slug === '' || Role::query()->where('scope', $profile->value)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A role with a similar name already exists. Choose a different name.']);
        }

        return DB::transaction(function () use ($profile, $slug, $name, $description, $permissionKeys, $actor): Role {
            $role = Role::query()->create([
                'scope' => $profile->value,
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'is_system' => false,
            ]);

            $role->permissions()->sync(Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all());

            sort($permissionKeys);
            $this->audit->record('role.created', 'role', $role->uuid, null, [
                'name' => $name,
                'permissions' => $permissionKeys,
            ], $actor);

            return $role;
        });
    }
}
