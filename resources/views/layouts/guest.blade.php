<!DOCTYPE html>
<html lang="en" data-accent="{{ $accent ?? 'navy' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') - {{ $orgName ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest">
    <main class="guest__panel" id="main">
        <div class="guest__brand">
            <h1>{{ $orgName ?? config('app.name') }}</h1>
            <p>{{ $profileLabel ?? '' }}</p>
        </div>
        @yield('content')
    </main>
</body>
</html>
