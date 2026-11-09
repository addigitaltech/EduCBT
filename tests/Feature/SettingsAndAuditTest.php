<?php

use App\Domain\Audit\AuditLogger;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Settings\SettingsService;
use Illuminate\Support\Facades\DB;

it('saves security settings within their limits and audits the change', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)->put('/settings/security', [
        'max_login_attempts' => 4,
        'lockout_minutes' => 30,
        'password_min_length' => 12,
    ])->assertRedirect(route('settings.security'));

    $settings = app(SettingsService::class);
    $settings->forgetMemo();
    expect($settings->get('security', 'max_login_attempts'))->toBe(4)
        ->and($settings->get('security', 'password_min_length'))->toBe(12);

    expect(AuditLog::query()->where('action', 'settings.security_changed')->exists())->toBeTrue();

    $this->put('/settings/security', ['max_login_attempts' => 1, 'lockout_minutes' => 0, 'password_min_length' => 3])
        ->assertSessionHasErrors(['max_login_attempts', 'lockout_minutes', 'password_min_length']);
});

it('applies the configured minimum password length', function (): void {
    app(SettingsService::class)->set('security', 'password_min_length', 14);
    $user = \App\Domain\Identity\Models\User::factory()->create();

    $this->actingAs($user)->put('/account/password', [
        'current_password' => \Database\Factories\UserFactory::PASSWORD,
        'password' => 'Short-pass-1',
        'password_confirmation' => 'Short-pass-1',
    ])->assertSessionHasErrors('password');
});

it('saves appearance and only allows the listed accent colours', function (): void {
    $this->actingAs(adminUser())->put('/settings/appearance', ['display_name' => 'Greenfield College', 'accent' => 'teal'])
        ->assertRedirect();

    $this->get('/settings/appearance')->assertSee('Greenfield College');
    $this->get('/')->assertSee('data-accent="teal"', false)->assertSee('Greenfield College');

    $this->put('/settings/appearance', ['display_name' => 'X', 'accent' => 'hotpink'])->assertSessionHasErrors('accent');
});

it('encrypts settings marked as secret', function (): void {
    $settings = app(SettingsService::class);
    $settings->set('integration', 'token', 'super-secret-value', true);

    expect(DB::table('app_settings')->where('key', 'token')->value('value'))->not->toContain('super-secret-value');

    $settings->forgetMemo();
    expect($settings->get('integration', 'token'))->toBe('super-secret-value');
});

it('falls back to the default when a setting is not stored', function (): void {
    expect(app(SettingsService::class)->get('security', 'lockout_minutes'))->toBe(15);
});

it('shows the audit log with filters and plain-language entries', function (): void {
    $admin = adminUser();
    app(AuditLogger::class)->record('test.something_happened', 'widget', null, ['a' => 1], ['a' => 2], $admin);

    $this->actingAs($admin)->get('/settings/audit-log')
        ->assertOk()
        ->assertSee('Test something happened');

    $this->get('/settings/audit-log?action=something_happened')->assertSee('Test something happened');
    $this->get('/settings/audit-log?action=zzz-nothing')->assertSee('No entries found');
    $this->get('/settings/audit-log?from=2030-01-02&to=2030-01-01')->assertSessionHasErrors('to');
});

it('redacts secrets before they reach the audit log', function (): void {
    $log = app(AuditLogger::class)->record('test.redact', 'widget', null, null, [
        'name' => 'ok',
        'password' => 'hunter2',
        'nested' => ['token' => 'abc', 'keep' => 'yes'],
    ]);

    expect($log->after_json)->toBe([
        'name' => 'ok',
        'password' => '[redacted]',
        'nested' => ['token' => '[redacted]', 'keep' => 'yes'],
    ]);
});

it('refuses to change or delete audit entries', function (): void {
    $log = app(AuditLogger::class)->record('test.immutable');

    expect(fn () => $log->update(['action' => 'changed']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
    expect(AuditLog::query()->whereKey($log->id)->value('action'))->toBe('test.immutable');
});

it('records who did it, from where, and the request id', function (): void {
    $admin = adminUser();
    $this->actingAs($admin)->put('/settings/appearance', ['display_name' => 'Some School', 'accent' => 'navy']);

    $log = AuditLog::query()->where('action', 'settings.appearance_changed')->firstOrFail();
    expect($log->actor_uuid)->toBe($admin->uuid)
        ->and($log->request_id)->toBeUuid()
        ->and($log->profile)->toBe(\App\Support\Profile\Profile::current()->value);
});
