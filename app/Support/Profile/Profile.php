<?php

namespace App\Support\Profile;

/**
 * The application profile this deployment runs as.
 */
enum Profile: string
{
    case Central = 'central';
    case School = 'school';

    public static function current(): self
    {
        return self::from((string) config('profile.name'));
    }

    public function isCentral(): bool
    {
        return $this === self::Central;
    }

    public function isSchool(): bool
    {
        return $this === self::School;
    }

    public function label(): string
    {
        return match ($this) {
            self::Central => 'Central platform',
            self::School => 'School server',
        };
    }
}
