<?php

use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\Hash;

function staffRole(string $slug): Role
{
    return Role::query()->where('scope', Profile::current()->value)->where('slug', $slug)->firstOrFail();
}

function firstAssignableSlug(): string
{
    foreach (RoleCatalog::for(Profile::current()) as $role) {
        if ($role['assignable'] && ! $role['locked'] && $role['permissions'] === []) {
            return $role['slug'];
        }
    }

    throw new RuntimeException('No assignable role in catalog.');
}

it('adds a user with a one-time temporary password and a forced change', function (): void {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Ada Obi',
        'login_id' => 'Ada.Obi',
        'email' => 'ada@example.test',
        'roles' => [staffRole(firstAssignableSlug())->id],
    ]);

    $user = User::query()->where('login_id', 'ada.obi')->firstOrFail();
    $response->assertRedirect(route('users.edit', $user));

    expect($user->user_type)->toBe(UserType::staffFor(Profile::current()))
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->roles)->toHaveCount(1);

    $temporary = session('temporary_password');
    expect($temporary)->toBeString()->and(Hash::check($temporary, $user->password))->toBeTrue();

    $this->get(route('users.edit', $user))->assertSee($temporary);
    $this->get(route('users.edit', $user))->assertDontSee($temporary);
});

it('rejects a duplicate or badly formed sign-in ID', function (): void {
    User::factory()->create(['login_id' => 'taken.id']);

    $this->actingAs(adminUser())
        ->post('/settings/users', ['name' => 'X Y', 'login_id' => 'TAKEN.id'])
        ->assertSessionHasErrors('login_id');

    $this->post('/settings/users', ['name' => 'X Y', 'login_id' => 'has space'])
        ->assertSessionHasErrors('login_id');
});

it('never shows or stores the temporary password in the audit log', function (): void {
    $this->actingAs(adminUser())->post('/settings/users', ['name' => 'Ada Obi', 'login_id' => 'ada.obi']);

    $temporary = session('temporary_password');
    expect(AuditLog::query()->get()->toJson())->not->toContain($temporary);
    expect(AuditLog::query()->where('action', 'user.created')->exists())->toBeTrue();
});

it('cannot reach staff of the other user types or invent unknown users', function (): void {
    $student = User::factory()->create(['user_type' => UserType::Student]);

    $this->actingAs(adminUser())->get(route('users.edit', $student))->assertNotFound();
    $this->get('/settings/users/'.fake()->uuid().'/edit')->assertNotFound();
});

it('lists, searches and filters users', function (): void {
    $admin = adminUser();
    User::factory()->create(['name' => 'Zainab Bello', 'login_id' => 'zainab']);
    User::factory()->create(['name' => 'Chidi Eze', 'login_id' => 'chidi']);
    User::factory()->status(UserStatus::Inactive)->create(['name' => 'Old Timer', 'login_id' => 'oldtimer']);

    $this->actingAs($admin)->get('/settings/users?q=zain')->assertSee('Zainab Bello')->assertDontSee('Chidi Eze');
    $this->get('/settings/users?status=inactive')->assertSee('Old Timer')->assertDontSee('Chidi Eze');
    $this->get('/settings/users?q=nobody-matches')->assertSee('No users found');
});

it('updates name and email and records the change', function (): void {
    $admin = adminUser();
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)->put(route('users.update', $user), ['name' => 'New Name', 'email' => 'new@example.test'])
        ->assertRedirect();

    expect($user->fresh()->name)->toBe('New Name');
    expect(AuditLog::query()->where('action', 'user.updated')->exists())->toBeTrue();
});

it('resets a password to a new temporary one that must be changed', function (): void {
    $admin = adminUser();
    $user = User::factory()->create(['failed_attempts' => 2]);

    $this->actingAs($admin)->post(route('users.reset-password', $user))->assertRedirect();

    $user->refresh();
    $temporary = session('temporary_password');
    expect($user->must_change_password)->toBeTrue()
        ->and(Hash::check($temporary, $user->password))->toBeTrue()
        ->and(Hash::check(\Database\Factories\UserFactory::PASSWORD, $user->password))->toBeFalse();
});

it('deactivates and reactivates a user', function (): void {
    $admin = adminUser();
    $user = User::factory()->create();

    $this->actingAs($admin)->post(route('users.status', $user), ['status' => 'inactive'])->assertRedirect();
    expect($user->fresh()->status)->toBe(UserStatus::Inactive);

    $this->post(route('users.status', $user), ['status' => 'active'])->assertRedirect();
    expect($user->fresh()->status)->toBe(UserStatus::Active);

    expect(AuditLog::query()->where('action', 'user.status_changed')->count())->toBe(2);
});

it('does not let anyone deactivate themselves or the last administrator', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('users.status', $admin), ['status' => 'inactive'])
        ->assertSessionHasErrors('status');

    $second = userWith(['users.manage', 'users.view']);
    $this->actingAs($second)->post(route('users.status', $admin), ['status' => 'inactive'])
        ->assertSessionHasErrors('status');

    expect($admin->fresh()->status)->toBe(UserStatus::Active);
});

it('does not let the last administrator lose the administrator role', function (): void {
    $admin = adminUser();
    $second = userWith(['users.manage', 'users.view', 'roles.view']);
    // Give the second user every permission so the escalation check passes and only the last-admin rule applies.
    $second->roles()->first()->permissions()->sync(\App\Domain\Access\Models\Permission::query()->pluck('id')->all());
    $second->forgetPermissionCache();

    $this->actingAs($second)->put(route('users.update', $admin), ['name' => $admin->name, 'roles' => []])
        ->assertSessionHasErrors('roles');

    expect($admin->roles()->count())->toBe(1);
});

it('does not let someone hand out a role that allows more than they can do', function (): void {
    $limited = userWith(['users.manage', 'users.view']);
    $target = User::factory()->create();
    $adminRole = staffRole(RoleCatalog::administratorSlug(Profile::current()));

    $this->actingAs($limited)->put(route('users.update', $target), ['name' => $target->name, 'roles' => [$adminRole->id]])
        ->assertSessionHasErrors('roles');

    expect($target->roles()->count())->toBe(0);
});

it('does not allow roles that are not for staff or belong to the other profile', function (): void {
    $target = User::factory()->create();
    $roleIds = [];

    foreach (RoleCatalog::for(Profile::current()) as $role) {
        if (! $role['assignable']) {
            $roleIds[] = staffRole($role['slug'])->id;
        }
    }

    if ($roleIds !== []) {
        $this->actingAs(adminUser())->put(route('users.update', $target), ['name' => $target->name, 'roles' => $roleIds])
            ->assertSessionHasErrors('roles');
    }

    $this->actingAs(adminUser())->put(route('users.update', $target), ['name' => $target->name, 'roles' => [999999]])
        ->assertSessionHasErrors('roles');

    expect($target->roles()->count())->toBe(0);
});
