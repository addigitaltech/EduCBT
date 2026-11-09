<?php

namespace App\Domain\Access;

use App\Support\Profile\Profile;

/**
 * The built-in (system) roles for each profile. Permissions for most roles
 * arrive with the modules that need them in later phases.
 */
final class RoleCatalog
{
    /**
     * "locked" roles always hold every permission and cannot be edited, so an
     * installation can never lose its administrator access.
     * "assignable" roles can be given to staff users from the Users screen.
     *
     * @return list<array{slug: string, name: string, description: string, permissions: string|list<string>, locked: bool, assignable: bool}>
     */
    public static function for(Profile $profile): array
    {
        return match ($profile) {
            Profile::Central => [
                self::role('super_administrator', 'Super administrator', 'Full access to the central platform.', '*', true),
                self::role('platform_administrator', 'Platform administrator', 'Manages users and settings.', ['users.view', 'users.manage', 'roles.view', 'settings.view', 'audit.view', 'system.health.view']),
                self::role('question_bank_administrator', 'Question bank administrator', 'Manages the central question bank.', []),
                self::role('question_reviewer', 'Question reviewer', 'Reviews and approves questions.', []),
                self::role('finance_administrator', 'Finance administrator', 'Manages subscriptions and payments.', []),
                self::role('technical_support_administrator', 'Technical support administrator', 'Helps schools with technical issues.', ['audit.view', 'system.health.view']),
            ],
            Profile::School => [
                self::role('school_administrator', 'School administrator', 'Full access to this school installation.', '*', true),
                self::role('principal', 'Principal', 'Oversees results and school activity.', ['audit.view']),
                self::role('examination_officer', 'Examination officer', 'Runs examinations and reviews scores.', []),
                self::role('teacher', 'Teacher', 'Teaches assigned classes and subjects.', []),
                self::role('class_teacher', 'Class teacher', 'Looks after one class.', []),
                self::role('student', 'Student', 'Sits examinations and sees published results.', [], false, false),
                self::role('parent_guardian', 'Parent or guardian', 'Sees published results for linked children.', [], false, false),
            ],
        };
    }

    public static function administratorSlug(Profile $profile): string
    {
        return $profile->isCentral() ? 'super_administrator' : 'school_administrator';
    }

    /** Locked roles always hold every permission and cannot be edited or deleted. */
    public static function isLocked(Profile $profile, string $slug): bool
    {
        foreach (self::for($profile) as $role) {
            if ($role['slug'] === $slug) {
                return $role['locked'];
            }
        }

        return false;
    }

    /** Custom roles (not in the catalog) can always be assigned to staff. */
    public static function isAssignable(Profile $profile, string $slug): bool
    {
        foreach (self::for($profile) as $role) {
            if ($role['slug'] === $slug) {
                return $role['assignable'];
            }
        }

        return true;
    }

    public static function isSystem(Profile $profile, string $slug): bool
    {
        foreach (self::for($profile) as $role) {
            if ($role['slug'] === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string|list<string>  $permissions
     * @return array{slug: string, name: string, description: string, permissions: string|list<string>, locked: bool, assignable: bool}
     */
    private static function role(string $slug, string $name, string $description, string|array $permissions, bool $locked = false, bool $assignable = true): array
    {
        return compact('slug', 'name', 'description', 'permissions', 'locked', 'assignable');
    }
}
