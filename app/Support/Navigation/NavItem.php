<?php

namespace App\Support\Navigation;

final class NavItem
{
    /**
     * @param  list<string>  $activePatterns  route-name patterns that mark this item as current
     */
    public function __construct(
        public readonly string $label,
        public readonly string $route,
        public readonly string $icon,
        public readonly array $activePatterns = [],
    ) {}

    public function isActive(): bool
    {
        return request()->routeIs($this->route, ...$this->activePatterns);
    }
}
