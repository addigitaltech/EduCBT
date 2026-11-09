<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\User;
use App\Domain\Settings\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Checks sign-in details. Every failure looks the same to the person signing in,
 * so the screen never reveals whether a sign-in ID exists. The real reason goes
 * to the audit log.
 */
final class AttemptLogin
{
    private static ?string $dummyHash = null;

    public function __construct(
        private SettingsService $settings,
        private AuditLogger $audit,
    ) {}

    public function handle(string $loginId, string $password, bool $remember): bool
    {
        $loginId = Str::lower(trim($loginId));
        $user = User::query()->where('login_id', $loginId)->first();

        if ($user === null) {
            // Spend the same time as a real check so timing does not reveal the account.
            Hash::check($password, self::dummyHash());
            $this->audit->record('auth.login_failed', 'user', null, null, ['login_id' => $loginId, 'reason' => 'unknown_account']);

            return false;
        }

        if (! $user->canSignIn()) {
            Hash::check($password, self::dummyHash());
            $this->audit->record('auth.login_failed', 'user', $user->uuid, null, [
                'login_id' => $loginId,
                'reason' => $user->isActive() ? 'temporarily_locked' : 'account_'.$user->status->value,
            ], $user);

            return false;
        }

        if (! Hash::check($password, $user->password)) {
            $this->registerFailure($user);

            return false;
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $password]);
        }

        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        Auth::guard('web')->login($user, $remember);
        $this->audit->record('auth.login_succeeded', 'user', $user->uuid, null, null, $user);

        return true;
    }

    private function registerFailure(User $user): void
    {
        $max = (int) $this->settings->get('security', 'max_login_attempts');
        $minutes = (int) $this->settings->get('security', 'lockout_minutes');

        $attempts = $user->failed_attempts + 1;
        $this->audit->record('auth.login_failed', 'user', $user->uuid, null, [
            'login_id' => $user->login_id,
            'reason' => 'bad_password',
            'attempt' => $attempts,
        ], $user);

        if ($attempts >= $max) {
            $user->forceFill(['failed_attempts' => 0, 'locked_until' => now()->addMinutes($minutes)])->save();
            $this->audit->record('auth.account_locked', 'user', $user->uuid, null, [
                'locked_for_minutes' => $minutes,
            ], $user);

            return;
        }

        $user->forceFill(['failed_attempts' => $attempts])->save();
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(24));
    }
}
