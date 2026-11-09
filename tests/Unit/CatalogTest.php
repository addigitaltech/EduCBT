<?php

use App\Domain\Access\PermissionRegistry;
use App\Domain\Access\RoleCatalog;
use App\Domain\Identity\Enums\UserType;
use App\Support\Profile\Profile;
use App\Support\Sql\Like;

dataset('profiles', [
    'central' => [Profile::Central],
    'school' => [Profile::School],
]);

it('has unique role slugs and exactly one locked administrator role', function (Profile $profile): void {
    $roles = RoleCatalog::for($profile);
    $slugs = array_column($roles, 'slug');

    expect($slugs)->toBe(array_values(array_unique($slugs)));

    $locked = array_values(array_filter($roles, fn ($role) => $role['locked']));
    expect($locked)->toHaveCount(1)
        ->and($locked[0]['slug'])->toBe(RoleCatalog::administratorSlug($profile))
        ->and($locked[0]['permissions'])->toBe('*');
})->with('profiles');

it('only references permissions that exist for that profile', function (Profile $profile): void {
    $known = array_keys(PermissionRegistry::forProfile($profile));

    foreach (RoleCatalog::for($profile) as $role) {
        if ($role['permissions'] === '*') {
            continue;
        }

        expect(array_diff($role['permissions'], $known))->toBe([]);
    }
})->with('profiles');

it('never makes a locked role assignable-by-accident or a non-assignable role locked', function (Profile $profile): void {
    foreach (RoleCatalog::for($profile) as $role) {
        if (! $role['assignable']) {
            expect($role['locked'])->toBeFalse();
        }
    }
})->with('profiles');

it('describes every permission in plain language with a group', function (): void {
    foreach (PermissionRegistry::all() as $key => $definition) {
        expect($key)->toMatch('/^[a-z]+(\.[a-z]+)+$/')
            ->and($definition['group'])->not->toBe('')
            ->and($definition['description'])->not->toBe('')
            ->and($definition['scope'])->toBeIn(['shared', 'central', 'school']);
    }
});

it('limits each user type to the profile it belongs to', function (): void {
    expect(UserType::CentralStaff->allowedIn(Profile::Central))->toBeTrue()
        ->and(UserType::CentralStaff->allowedIn(Profile::School))->toBeFalse()
        ->and(UserType::SchoolStaff->allowedIn(Profile::School))->toBeTrue()
        ->and(UserType::SchoolStaff->allowedIn(Profile::Central))->toBeFalse()
        ->and(UserType::Student->allowedIn(Profile::Central))->toBeFalse()
        ->and(UserType::Guardian->allowedIn(Profile::Central))->toBeFalse();
});

it('picks the staff user type from the profile', function (): void {
    expect(UserType::staffFor(Profile::Central))->toBe(UserType::CentralStaff)
        ->and(UserType::staffFor(Profile::School))->toBe(UserType::SchoolStaff);
});

it('escapes LIKE wildcards', function (): void {
    expect(Like::contains('100%_done!'))->toBe('%100!%!_done!!%');
});
