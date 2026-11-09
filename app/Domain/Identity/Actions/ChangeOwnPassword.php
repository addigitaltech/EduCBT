<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;

final class ChangeOwnPassword
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
        ])->save();

        $this->audit->record('auth.password_changed', 'user', $user->uuid, null, null, $user);
    }
}
