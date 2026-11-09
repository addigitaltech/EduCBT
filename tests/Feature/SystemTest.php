<?php

use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Identity\Models\User;
use App\Domain\System\Models\SystemIdentity;
use App\Domain\System\Support\HealthChecker;
use App\Support\Profile\Profile;
use Database\Seeders\AccessSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

it('shows plain-language health checks to people allowed to see them', function (): void {
    $this->actingAs(userWith(['system.health.view']))->get('/system/health')
        ->assertOk()
        ->assertSee('Database')
        ->assertSee('Disk space')
        ->assertSee('Background jobs')
        ->assertSee('Scheduled tasks');
});

it('reports a stale scheduler as a warning and a recent one as working', function (): void {
    $checker = app(HealthChecker::class);
    $find = fn () => collect($checker->run())->firstWhere('key', 'scheduler');

    Cache::forget('scheduler.heartbeat');
    expect($find()['status'])->toBe(HealthChecker::WARNING);

    Cache::put('scheduler.heartbeat', time() - 600);
    expect($find()['status'])->toBe(HealthChecker::WARNING);

    Cache::put('scheduler.heartbeat', time());
    expect($find()['status'])->toBe(HealthChecker::OK);
});

it('combines checks into one overall status', function (): void {
    $checker = app(HealthChecker::class);

    expect($checker->overall([['status' => 'ok'], ['status' => 'ok']]))->toBe('ok')
        ->and($checker->overall([['status' => 'ok'], ['status' => 'warning']]))->toBe('warning')
        ->and($checker->overall([['status' => 'warning'], ['status' => 'problem']]))->toBe('problem');
});

it('seeds permissions and roles idempotently and keeps locked roles at full access', function (): void {
    $slug = RoleCatalog::administratorSlug(Profile::current());
    $role = Role::query()->where('scope', Profile::current()->value)->where('slug', $slug)->firstOrFail();
    $role->permissions()->detach();

    (new AccessSeeder)->run();
    (new AccessSeeder)->run();

    expect($role->permissions()->count())->toBe(count(allPermissionKeys()))
        ->and(Role::query()->where('scope', Profile::current()->value)->count())->toBe(count(RoleCatalog::for(Profile::current())));
});

it('does not overwrite changes made to an unlocked built-in role when seeding again', function (): void {
    $slug = collect(RoleCatalog::for(Profile::current()))->first(fn ($r) => ! $r['locked'] && $r['permissions'] === [])['slug'];
    $role = Role::query()->where('scope', Profile::current()->value)->where('slug', $slug)->firstOrFail();
    $role->permissions()->sync(\App\Domain\Access\Models\Permission::query()->where('key', 'users.view')->pluck('id')->all());

    (new AccessSeeder)->run();

    expect($role->permissions()->pluck('key')->all())->toBe(['users.view']);
});

it('installs a fresh installation with the first administrator', function (): void {
    SystemIdentity::query()->delete();
    User::query()->delete();

    $this->artisan('app:install', ['--login' => 'First.Admin', '--name' => 'First Admin', '--password' => 'Long-enough-pass1'])
        ->assertSuccessful();

    $identity = SystemIdentity::current();
    expect($identity->profile)->toBe(Profile::current()->value)
        ->and($identity->installation_uuid)->toBeUuid();

    $admin = User::query()->where('login_id', 'first.admin')->firstOrFail();
    expect($admin->permissionKeys())->toEqualCanonicalizing(allPermissionKeys())
        ->and($admin->must_change_password)->toBeFalse();
});

it('generates a temporary password when none is given and forces a change', function (): void {
    SystemIdentity::query()->delete();
    User::query()->delete();

    $this->artisan('app:install', ['--login' => 'gen.admin', '--name' => 'Gen Admin'])
        ->expectsQuestion('Password (press Enter to generate a temporary one)', '')
        ->assertSuccessful();

    expect(User::query()->where('login_id', 'gen.admin')->firstOrFail()->must_change_password)->toBeTrue();
});

it('refuses to install twice', function (): void {
    $before = SystemIdentity::current()->installation_uuid;

    $this->artisan('app:install', ['--login' => 'again', '--name' => 'Again', '--password' => 'Long-enough-pass1'])
        ->assertFailed();

    expect(SystemIdentity::current()->installation_uuid)->toBe($before);
});

it('refuses weak install details', function (): void {
    SystemIdentity::query()->delete();

    $this->artisan('app:install', ['--login' => 'ab', '--name' => 'Short', '--password' => 'short'])->assertFailed();

    expect(SystemIdentity::current())->toBeNull();
});
