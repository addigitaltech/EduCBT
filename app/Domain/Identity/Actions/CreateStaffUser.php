<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateStaffUser
{
    public function __construct(
        private AuditLogger $audit,
        private SyncUserRoles $syncRoles,
    ) {}

    /**
     * @param  array{name: string, login_id: string, email?: string|null, roles?: list<int|string>}  $data
     * @return array{0: User, 1: string} The new user and the one-time temporary password.
     */
    public function handle(array $data, User $actor): array
    {
        $temporaryPassword = Str::password(14, symbols: false);

        $user = DB::transaction(function () use ($data, $actor, $temporaryPassword): User {
            $user = User::query()->create([
                'user_type' => UserType::staffFor(Profile::current()),
                'login_id' => $data['login_id'],
                'email' => $data['email'] ?? null,
                'name' => $data['name'],
                'password' => $temporaryPassword,
                'status' => UserStatus::Active,
                'must_change_password' => true,
            ]);

            $this->audit->record('user.created', 'user', $user->uuid, null, [
                'name' => $user->name,
                'login_id' => $user->login_id,
                'email' => $user->email,
            ], $actor);

            $this->syncRoles->handle($user, $data['roles'] ?? [], $actor);

            return $user;
        });

        return [$user, $temporaryPassword];
    }
}
