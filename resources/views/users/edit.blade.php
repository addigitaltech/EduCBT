@extends('layouts.app')

@section('title', $user->name)

@section('content')
    @include('partials.settings-tabs')

    @php($canManage = auth()->user()->can('users.manage'))
    @php($isSelf = $user->is(auth()->user()))

    <div class="page-header">
        <div>
            <h2>{{ $user->name }}</h2>
            <p>
                Sign-in ID: <strong>{{ $user->login_id }}</strong> &middot;
                <span class="badge {{ $user->isActive() ? 'badge--ok' : 'badge--neutral' }}">{{ $user->status->label() }}</span>
            </p>
        </div>
        <a class="btn btn-secondary" href="{{ route('users.index') }}">Back to users</a>
    </div>

    @error('status')
        <div class="alert alert--bad" role="alert"><div>{{ $message }}</div></div>
    @enderror

    <div class="stack-lg" style="max-width: 36rem">
        <form method="POST" action="{{ route('users.update', $user) }}" class="card stack" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')
            <x-field name="name" label="Full name" required :value="$user->name" :readonly="! $canManage" />
            <x-field name="login_id_display" label="Sign-in ID" :value="$user->login_id" readonly hint="The sign-in ID cannot be changed." />
            <x-field name="email" label="Email address" type="email" :value="$user->email" :readonly="! $canManage" />

            @if ($canManage)
                @include('users._roles', ['roles' => $roles, 'selected' => $user->roles->pluck('id')->all()])
                <div class="row">
                    <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Saving...' : 'Save changes'">Save changes</span></button>
                </div>
            @else
                <p class="muted">Roles: {{ $user->roles->pluck('name')->join(', ') ?: 'No role' }}</p>
            @endif
        </form>

        @if ($canManage)
            <section class="card stack">
                <h2>Account access</h2>

                <form method="POST" action="{{ route('users.reset-password', $user) }}"
                      x-data @submit="if (! confirm('Reset the password for {{ addslashes($user->name) }}? Their current password will stop working and they must choose a new one at next sign-in.')) $event.preventDefault()">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><i data-lucide="key-round" class="icon" aria-hidden="true"></i><span>Reset password</span></button>
                </form>

                @unless ($isSelf)
                    @if ($user->isActive())
                        <form method="POST" action="{{ route('users.status', $user) }}"
                              x-data @submit="if (! confirm('Deactivate {{ addslashes($user->name) }}? They will be signed out and cannot sign in until you activate them again. Their records are kept.')) $event.preventDefault()">
                            @csrf
                            <input type="hidden" name="status" value="inactive">
                            <button type="submit" class="btn btn-danger"><i data-lucide="user-x" class="icon" aria-hidden="true"></i><span>Deactivate user</span></button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('users.status', $user) }}">
                            @csrf
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="btn btn-primary"><i data-lucide="user-check" class="icon" aria-hidden="true"></i><span>Activate user</span></button>
                        </form>
                    @endif
                @endunless
            </section>
        @endif
    </div>
@endsection
