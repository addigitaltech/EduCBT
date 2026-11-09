<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

final class SetUserStatus
{
    public function __construct(
        private AuditLogger $audit,
        private LastAdministratorGuard $guard,
    ) {}

    public function handle(User $user, UserStatus $status, User $actor): void
    {
        if ($status === UserStatus::Inactive) {
            if ($user->is($actor)) {
                throw ValidationException::withMessages(['status' => 'You cannot deactivate your own account.']);
            }

            $this->guard->assertCanDeactivate($user);
        }

        $before = ['status' => $user->status->value];

        $user->forceFill([
            'status' => $status,
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->audit->record('user.status_changed', 'user', $user->uuid, $before, ['status' => $status->value], $actor);
    }
}
