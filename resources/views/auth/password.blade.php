@extends('layouts.app')

@section('title', 'Change password')

@section('content')
    <div class="page-header">
        <div>
            <h1>Change password</h1>
            @if ($forced)
                <p>You are using a temporary password. Choose your own to continue.</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="card stack" style="max-width: 32rem" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @method('PUT')

        <x-field name="current_password" label="Current password" type="password" required autocomplete="current-password" />
        <x-field name="password" label="New password" type="password" required autocomplete="new-password"
                 hint="Use at least {{ (int) app(\App\Domain\Settings\SettingsService::class)->get('security', 'password_min_length') }} characters, with letters and numbers." />
        <x-field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />

        <div class="row">
            <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Saving...' : 'Change password'">Change password</span></button>
            @unless ($forced)
                <a class="btn btn-secondary" href="{{ route('dashboard') }}">Cancel</a>
            @endunless
        </div>
    </form>
@endsection
