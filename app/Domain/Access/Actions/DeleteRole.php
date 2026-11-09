<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Role;
use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteRole
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Role $role, User $actor): void
    {
        if ($role->scope !== Profile::current()->value) {
            abort(404);
        }

        if ($role->is_system) {
            throw ValidationException::withMessages(['role' => 'Built-in roles cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'This role is still given to users. Remove it from them first.']);
        }

        DB::transaction(function () use ($role, $actor): void {
            $this->audit->record('role.deleted', 'role', $role->uuid, ['name' => $role->name], null, $actor);
            $role->permissions()->detach();
            $role->delete();
        });
    }
}
