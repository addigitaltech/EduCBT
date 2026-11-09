@extends('layouts.app')

@section('title', 'Security settings')

@section('content')
    @include('partials.settings-tabs')

    @php($canManage = auth()->user()->can('settings.manage'))

    <div class="page-header">
        <div>
            <h2>Security</h2>
            <p>How sign-in and passwords are protected.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.security.update') }}" class="card stack" style="max-width: 36rem" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @method('PUT')

        <x-field name="max_login_attempts" label="Wrong passwords before an account is locked" type="number" required :value="$values['max_login_attempts']" :readonly="! $canManage"
                 hint="Between 3 and 20. After this many wrong passwords in a row, the account is locked for a while." />
        <x-field name="lockout_minutes" label="Minutes an account stays locked" type="number" required :value="$values['lockout_minutes']" :readonly="! $canManage"
                 hint="Between 1 and 1440. An administrator can unlock an account sooner by resetting its password." />
        <x-field name="password_min_length" label="Shortest allowed password" type="number" required :value="$values['password_min_length']" :readonly="! $canManage"
                 hint="Between 8 and 64 characters. Passwords must also contain letters and numbers. Applies to new passwords only." />

        @if ($canManage)
            <div class="row">
                <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Saving...' : 'Save changes'">Save changes</span></button>
            </div>
        @endif
    </form>
@endsection
