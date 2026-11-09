<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Str;

final class ResetUserPassword
{
    public function __construct(private AuditLogger $audit) {}

    /** @return string The one-time temporary password. */
    public function handle(User $user, User $actor): string
    {
        $temporaryPassword = Str::password(14, symbols: false);

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->audit->record('user.password_reset', 'user', $user->uuid, null, null, $actor);

        return $temporaryPassword;
    }
}
