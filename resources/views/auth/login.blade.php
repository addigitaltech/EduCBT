@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    @if (session('status'))
        <div class="alert alert--ok" role="status"><div>{{ session('status') }}</div></div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="stack" x-data="{ show: false, busy: false }" @submit="busy = true">
        @csrf

        <x-field name="login_id" label="Sign-in ID" required autocomplete="username" autofocus
                 hint="Your administrator gave you this." />

        <div class="field">
            <label for="password">Password</label>
            <div class="input-group">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" @class(['is-invalid' => $errors->has('password')])>
                <button type="button" @click="show = !show" :aria-pressed="show.toString()" aria-label="Show password">
                    <span x-text="show ? 'Hide' : 'Show'">Show</span>
                </button>
            </div>
            @error('password')
                <p class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Keep me signed in on this device<small>Only use this on a device that is not shared.</small></span>
        </label>

        <button type="submit" class="btn btn-primary btn-block" :disabled="busy">
            <span x-text="busy ? 'Signing in...' : 'Sign in'">Sign in</span>
        </button>

        <p><a href="{{ route('credentials.help') }}">Forgot your password?</a></p>
    </form>
@endsection
