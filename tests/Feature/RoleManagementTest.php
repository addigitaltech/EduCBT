<?php

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;

it('lists the built-in roles for this profile only', function (): void {
    $names = collect(RoleCatalog::for(Profile::current()))->pluck('name');
    $page = $this->actingAs(adminUser())->get('/settings/roles')->assertOk();

    foreach ($names as $name) {
        $page->assertSee($name);
    }

    $other = Profile::current()->isCentral() ? Profile::School : Profile::Central;
    $foreign = collect(RoleCatalog::for($other))->pluck('name')->diff($names);
    foreach ($foreign as $name) {
        $page->assertDontSee($name);
    }
});

it('creates a custom role with chosen permissions', function (): void {
    $this->actingAs(adminUser())->post('/settings/roles', [
        'name' => 'Records clerk',
        'description' => 'Views users',
        'permissions' => ['users.view'],
    ])->assertRedirect();

    $role = Role::query()->where('name', 'Records clerk')->firstOrFail();
    expect($role->is_system)->toBeFalse()
        ->and($role->permissions->pluck('key')->all())->toBe(['users.view']);
    expect(AuditLog::query()->where('action', 'role.created')->exists())->toBeTrue();
});

it('rejects a duplicate role name and unknown permissions', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)->post('/settings/roles', ['name' => 'Records clerk', 'permissions' => ['users.view']])->assertRedirect();
    $this->post('/settings/roles', ['name' => 'Records Clerk', 'permissions' => []])->assertSessionHasErrors('name');
    $this->post('/settings/roles', ['name' => 'Other role', 'permissions' => ['made.up']])->assertSessionHasErrors('permissions');
});

it('changes the permissions of an editable role and records before and after', function (): void {
    $admin = adminUser();
    $role = Role::query()->create(['scope' => Profile::current()->value, 'slug' => 'custom_one', 'name' => 'Custom one', 'is_system' => false]);

    $this->actingAs($admin)->put(route('roles.update', $role), ['permissions' => ['users.view', 'audit.view']])->assertRedirect();

    expect($role->permissions()->pluck('key')->sort()->values()->all())->toBe(['audit.view', 'users.view']);

    $log = AuditLog::query()->where('action', 'role.permissions_changed')->latest('id')->firstOrFail();
    expect($log->before_json['permissions'])->toBe([])->and($log->after_json['permissions'])->toBe(['audit.view', 'users.view']);
});

it('never lets the administrator role be changed or deleted', function (): void {
    $admin = adminUser();
    $role = Role::query()->where('scope', Profile::current()->value)->where('slug', RoleCatalog::administratorSlug(Profile::current()))->firstOrFail();

    $this->actingAs($admin)->put(route('roles.update', $role), ['permissions' => ['users.view']])->assertSessionHasErrors('permissions');
    $this->delete(route('roles.destroy', $role))->assertSessionHasErrors('role');

    expect($role->permissions()->count())->toBe(count(allPermissionKeys()));
});

it('stops people from giving a role permissions they do not hold themselves', function (): void {
    $limited = userWith(['roles.manage', 'roles.view', 'users.view']);
    $role = Role::query()->create(['scope' => Profile::current()->value, 'slug' => 'custom_two', 'name' => 'Custom two', 'is_system' => false]);

    $this->actingAs($limited)->put(route('roles.update', $role), ['permissions' => ['users.view', 'audit.view']])
        ->assertSessionHasErrors('permissions');

    $this->post('/settings/roles', ['name' => 'Sneaky', 'permissions' => ['audit.view']])
        ->assertSessionHasErrors('permissions');

    expect($role->permissions()->count())->toBe(0);
});

it('keeps permissions the editor cannot change when saving a role', function (): void {
    $limited = userWith(['roles.manage', 'roles.view', 'users.view']);
    $role = Role::query()->create(['scope' => Profile::current()->value, 'slug' => 'custom_three', 'name' => 'Custom three', 'is_system' => false]);
    $role->permissions()->sync(Permission::query()->whereIn('key', ['users.view', 'audit.view'])->pluck('id')->all());

    // The form sends the permission the editor cannot change as a hidden field.
    $this->actingAs($limited)->put(route('roles.update', $role), ['permissions' => ['users.view', 'audit.view']])->assertRedirect();

    expect($role->permissions()->pluck('key')->sort()->values()->all())->toBe(['audit.view', 'users.view']);
});

it('deletes an unused custom role but not one that is in use or built in', function (): void {
    $admin = adminUser();
    $unused = Role::query()->create(['scope' => Profile::current()->value, 'slug' => 'unused', 'name' => 'Unused', 'is_system' => false]);
    $used = Role::query()->create(['scope' => Profile::current()->value, 'slug' => 'in_use', 'name' => 'In use', 'is_system' => false]);
    User::factory()->create()->roles()->attach($used->id, ['assigned_by' => null, 'created_at' => now()]);

    $this->actingAs($admin)->delete(route('roles.destroy', $unused))->assertRedirect(route('roles.index'));
    expect(Role::query()->whereKey($unused->id)->exists())->toBeFalse();

    $this->delete(route('roles.destroy', $used))->assertSessionHasErrors('role');
    expect(Role::query()->whereKey($used->id)->exists())->toBeTrue();

    $builtIn = Role::query()->where('scope', Profile::current()->value)->where('is_system', true)->where('slug', '!=', RoleCatalog::administratorSlug(Profile::current()))->firstOrFail();
    $this->delete(route('roles.destroy', $builtIn))->assertSessionHasErrors('role');
});
