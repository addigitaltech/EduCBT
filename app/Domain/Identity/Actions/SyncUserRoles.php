<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Validation\ValidationException;

final class SyncUserRoles
{
    public function __construct(
        private AuditLogger $audit,
        private LastAdministratorGuard $guard,
    ) {}

    /**
     * @param  list<int|string>  $roleIds
     */
    public function handle(User $user, array $roleIds, User $actor): void
    {
        $profile = Profile::current();
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        $roles = Role::query()
            ->where('scope', $profile->value)
            ->whereIn('id', $roleIds)
            ->with('permissions:id,key')
            ->get();

        if ($roles->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['roles' => 'One or more selected roles are not valid.']);
        }

        foreach ($roles as $role) {
            if (! RoleCatalog::isAssignable($profile, $role->slug)) {
                throw ValidationException::withMessages(['roles' => "The role \"{$role->name}\" cannot be given to staff."]);
            }
        }

        $current = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();
        $toAttach = array_values(array_diff($roleIds, $current));
        $toDetach = array_values(array_diff($current, $roleIds));

        if ($toAttach === [] && $toDetach === []) {
            return;
        }

        // You can only give a role whose permissions you hold yourself.
        $actorKeys = $actor->permissionKeys();
        foreach ($roles->whereIn('id', $toAttach) as $role) {
            if (array_diff($role->permissions->pluck('key')->all(), $actorKeys) !== []) {
                throw ValidationException::withMessages([
                    'roles' => "You cannot give the role \"{$role->name}\" because it allows more than you can do yourself.",
                ]);
            }
        }

        $this->guard->assertCanChangeRoles($user, $roleIds);

        $before = $user->roles()->pluck('roles.slug')->sort()->values()->all();

        $user->roles()->detach($toDetach);
        foreach ($toAttach as $id) {
            $user->roles()->attach($id, ['assigned_by' => $actor->getKey(), 'created_at' => now()]);
        }
        $user->forgetPermissionCache();

        $after = $user->roles()->pluck('roles.slug')->sort()->values()->all();

        $this->audit->record('user.roles_changed', 'user', $user->uuid, ['roles' => $before], ['roles' => $after], $actor);
    }
}
