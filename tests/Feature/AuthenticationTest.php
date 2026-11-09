<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Settings\SettingsService;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\RateLimiter;

function signIn(string $loginId, string $password = UserFactory::PASSWORD)
{
    return test()->post('/login', ['login_id' => $loginId, 'password' => $password]);
}

it('shows the sign-in page to guests and redirects guests away from the dashboard', function (): void {
    $this->get('/login')->assertOk()->assertSee('Sign-in ID');
    $this->get('/')->assertRedirect('/login');
});

it('signs a user in with the correct details', function (): void {
    $user = User::factory()->create(['login_id' => 'Teacher.One']);

    signIn('teacher.one')->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('ignores letter case and spaces in the sign-in ID', function (): void {
    User::factory()->create(['login_id' => 'mary.jones']);

    signIn('  MARY.Jones ')->assertRedirect('/');
});

it('gives the same message for a wrong password, an unknown ID and a deactivated account', function (): void {
    User::factory()->create(['login_id' => 'active.user']);
    User::factory()->status(UserStatus::Inactive)->create(['login_id' => 'gone.user']);

    $messages = [];

    foreach ([['active.user', 'wrong-password-1'], ['nobody.here', 'wrong-password-1'], ['gone.user', UserFactory::PASSWORD]] as [$id, $password]) {
        signIn($id, $password)->assertSessionHasErrors('login_id');
        $messages[] = session('errors')->first('login_id');
    }

    expect(array_unique($messages))->toHaveCount(1);
    $this->assertGuest();
});

it('locks an account after too many wrong passwords and keeps it locked even with the right one', function (): void {
    app(SettingsService::class)->set('security', 'max_login_attempts', 3);
    $user = User::factory()->create(['login_id' => 'lock.me']);

    foreach (range(1, 3) as $i) {
        RateLimiter::clear('lock.me|127.0.0.1');
        signIn('lock.me', 'wrong-password-'.$i);
    }

    $user->refresh();
    expect($user->locked_until)->not->toBeNull()->and($user->locked_until->isFuture())->toBeTrue();

    RateLimiter::clear('lock.me|127.0.0.1');
    signIn('lock.me')->assertSessionHasErrors('login_id');
    $this->assertGuest();

    expect(AuditLog::query()->where('action', 'auth.account_locked')->exists())->toBeTrue();
});

it('allows sign-in again once the lock has expired', function (): void {
    $user = User::factory()->create(['login_id' => 'later.user', 'locked_until' => now()->subMinute()]);

    signIn('later.user')->assertRedirect('/');
    expect($user->fresh()->locked_until)->toBeNull();
});

it('limits repeated sign-in attempts from one address and ID', function (): void {
    foreach (range(1, 5) as $i) {
        signIn('someone', 'wrong-password-'.$i);
    }

    signIn('someone')->assertSessionHasErrors('login_id');
    expect(session('errors')->first('login_id'))->toContain('Too many sign-in attempts');
});

it('records successful and failed sign-ins in the audit log without secrets', function (): void {
    User::factory()->create(['login_id' => 'audit.user']);

    signIn('audit.user', 'wrong-password-1');
    signIn('audit.user');

    $actions = AuditLog::query()->pluck('action')->all();
    expect($actions)->toContain('auth.login_failed')->and($actions)->toContain('auth.login_succeeded');

    $dump = AuditLog::query()->get()->toJson();
    expect($dump)->not->toContain('wrong-password-1')->and($dump)->not->toContain(UserFactory::PASSWORD);
});

it('signs out and ends the session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});

it('signs out a user who is deactivated while signed in', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->forceFill(['status' => UserStatus::Inactive])->save();

    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

it('forces a temporary password to be changed before anything else', function (): void {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->get('/')->assertRedirect('/account/password');
    $this->actingAs($user)->get('/account/password')->assertOk()->assertSee('temporary password');
});

it('changes the password, clears the forced change, and rejects weak or wrong input', function (): void {
    $user = User::factory()->mustChangePassword()->create();
    $this->actingAs($user);

    $this->put('/account/password', [
        'current_password' => 'not-my-password',
        'password' => 'Brand-new-pass1',
        'password_confirmation' => 'Brand-new-pass1',
    ])->assertSessionHasErrors('current_password');

    $this->put('/account/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => 'short1',
        'password_confirmation' => 'short1',
    ])->assertSessionHasErrors('password');

    $this->put('/account/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => 'Brand-new-pass1',
        'password_confirmation' => 'Brand-new-pass1',
    ])->assertRedirect('/');

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(\Illuminate\Support\Facades\Hash::check('Brand-new-pass1', $user->password))->toBeTrue();
});

it('stores passwords hashed', function (): void {
    $user = User::factory()->create();

    expect($user->getRawOriginal('password'))->not->toBe(UserFactory::PASSWORD)
        ->and(\Illuminate\Support\Facades\Hash::check(UserFactory::PASSWORD, $user->getRawOriginal('password')))->toBeTrue();
});
