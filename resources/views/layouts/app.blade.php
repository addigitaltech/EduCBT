@php
    $navItems = \App\Support\Navigation\NavigationRegistry::for(auth()->user());
@endphp
<!DOCTYPE html>
<html lang="en" data-accent="{{ $accent ?? 'navy' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ $orgName ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <a href="#main" class="skip-link">Skip to main content</a>
    <div class="shell">
        <div class="drawer-backdrop" :class="{ 'is-open': drawer }" @click="drawer = false" aria-hidden="true"></div>

        <aside class="sidebar" :class="{ 'is-open': drawer }" aria-label="Main navigation">
            <div class="brand">
                <div class="brand__name">{{ $orgName ?? config('app.name') }}</div>
                <div class="brand__sub">{{ $profileLabel ?? '' }}</div>
            </div>
            <nav class="nav">
                @foreach ($navItems as $item)
                    <a href="{{ route($item->route) }}" @if ($item->isActive()) aria-current="page" @endif>
                        <i data-lucide="{{ $item->icon }}" class="icon" aria-hidden="true"></i>
                        <span>{{ $item->label }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="main">
            <header class="topbar">
                <button type="button" class="btn btn-secondary topbar__menu" @click="drawer = true" aria-label="Open menu">
                    <i data-lucide="menu" class="icon" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>
                <span class="muted">@yield('crumb')</span>

                <div class="usermenu" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" class="btn btn-secondary" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true">
                        <i data-lucide="circle-user-round" class="icon" aria-hidden="true"></i>
                        <span>{{ auth()->user()->name }}</span>
                    </button>
                    <div class="usermenu__panel" x-show="open" x-cloak>
                        <a href="{{ route('password.edit') }}">
                            <i data-lucide="key-round" class="icon" aria-hidden="true"></i>
                            <span>Change password</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">
                                <i data-lucide="log-out" class="icon" aria-hidden="true"></i>
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="content" id="main">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
