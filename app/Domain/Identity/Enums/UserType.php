<?php

namespace App\Domain\Identity\Enums;

use App\Support\Profile\Profile;

enum UserType: string
{
    case CentralStaff = 'central_staff';
    case SchoolStaff = 'school_staff';
    case Student = 'student';
    case Guardian = 'guardian';

    /** The staff user type that belongs to a profile. */
    public static function staffFor(Profile $profile): self
    {
        return $profile->isCentral() ? self::CentralStaff : self::SchoolStaff;
    }

    /** Whether this type may exist in the given profile's database. */
    public function allowedIn(Profile $profile): bool
    {
        return match ($this) {
            self::CentralStaff => $profile->isCentral(),
            self::SchoolStaff, self::Student, self::Guardian => $profile->isSchool(),
        };
    }
}
