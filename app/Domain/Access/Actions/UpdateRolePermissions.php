<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Access\PermissionRegistry;
use App\Domain\Access\RoleCatalog;
use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateRolePermissions
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<string>  $permissionKeys
     */
    public function handle(Role $role, array $permissionKeys, User $actor): void
    {
        $profile = Profile::current();

        if ($role->scope !== $profile->value) {
            abort(404);
        }

        if (RoleCatalog::isLocked($profile, $role->slug)) {
            throw ValidationException::withMessages([
                'permissions' => 'This role always has full access and cannot be changed.',
            ]);
        }

        $permissionKeys = array_values(array_unique($permissionKeys));
        $allowed = array_keys(PermissionRegistry::forProfile($profile));

        if (array_diff($permissionKeys, $allowed) !== []) {
            throw ValidationException::withMessages(['permissions' => 'One or more permissions are not valid.']);
        }

        $before = $role->permissions()->pluck('permissions.key')->sort()->values()->all();
        $added = array_diff($permissionKeys, $before);

        // You can only hand out permissions you hold yourself.
        if (array_diff($added, $actor->permissionKeys()) !== []) {
            throw ValidationException::withMessages([
                'permissions' => 'You can only give a role permissions that you have yourself.',
            ]);
        }

        DB::transaction(function () use ($role, $permissionKeys, $before, $actor): void {
            $ids = Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();
            $role->permissions()->sync($ids);

            sort($permissionKeys);
            $this->audit->record(
                'role.permissions_changed',
                'role',
                $role->uuid,
                ['permissions' => $before],
                ['permissions' => $permissionKeys],
                $actor,
            );
        });
    }
}
