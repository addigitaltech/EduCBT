<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateStaffUser
{
    public function __construct(
        private AuditLogger $audit,
        private SyncUserRoles $syncRoles,
    ) {}

    /**
     * @param  array{name: string, email?: string|null, roles?: list<int|string>}  $data
     */
    public function handle(User $user, array $data, User $actor, bool $canChangeRoles): void
    {
        DB::transaction(function () use ($user, $data, $actor, $canChangeRoles): void {
            $before = ['name' => $user->name, 'email' => $user->email];

            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
            ])->save();

            $after = ['name' => $user->name, 'email' => $user->email];

            if ($before !== $after) {
                $this->audit->record('user.updated', 'user', $user->uuid, $before, $after, $actor);
            }

            if ($canChangeRoles) {
                $this->syncRoles->handle($user, $data['roles'] ?? [], $actor);
            }
        });
    }
}
