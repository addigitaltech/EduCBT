<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Access\RoleCatalog;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Validation\ValidationException;

/**
 * Makes sure an installation can never lock itself out of administration.
 */
final class LastAdministratorGuard
{
    public function isAdministrator(User $user): bool
    {
        return $user->roles()->where('slug', $this->slug())->where('scope', Profile::current()->value)->exists();
    }

    public function assertCanDeactivate(User $user): void
    {
        if ($this->isAdministrator($user) && $this->activeAdministrators() <= 1) {
            throw ValidationException::withMessages([
                'status' => 'This is the last active administrator. Make another user an administrator first.',
            ]);
        }
    }

    /** @param  list<int>  $newRoleIds */
    public function assertCanChangeRoles(User $user, array $newRoleIds): void
    {
        if (! $this->isAdministrator($user)) {
            return;
        }

        $adminRoleId = (int) $user->roles()->where('slug', $this->slug())->value('roles.id');

        if (! in_array($adminRoleId, $newRoleIds, true) && $this->activeAdministrators() <= 1) {
            throw ValidationException::withMessages([
                'roles' => 'This is the last active administrator. Make another user an administrator first.',
            ]);
        }
    }

    private function activeAdministrators(): int
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('roles', fn ($query) => $query
                ->where('slug', $this->slug())
                ->where('scope', Profile::current()->value))
            ->count();
    }

    private function slug(): string
    {
        return RoleCatalog::administratorSlug(Profile::current());
    }
}
