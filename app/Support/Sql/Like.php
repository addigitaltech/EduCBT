<?php

namespace App\Support\Sql;

/**
 * Safe LIKE patterns that behave the same on SQLite, MariaDB and MySQL.
 * Use with:  whereRaw("column like ? escape '!'", [Like::contains($term)])
 */
final class Like
{
    public static function contains(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
