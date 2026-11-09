@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    @include('partials.settings-tabs')

    <div class="page-header">
        <div>
            <h2>Add user</h2>
            <p>The user gets a temporary password and must choose their own when they first sign in.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="card stack" style="max-width: 36rem" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-field name="name" label="Full name" required autocomplete="off" />
        <x-field name="login_id" label="Sign-in ID" required autocomplete="off"
                 hint="Letters, numbers and . _ @ / - only. This cannot be changed later." />
        <x-field name="email" label="Email address" type="email" autocomplete="off" />

        @include('users._roles', ['roles' => $roles, 'selected' => []])

        <div class="row">
            <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Adding...' : 'Add user'">Add user</span></button>
            <a class="btn btn-secondary" href="{{ route('users.index') }}">Cancel</a>
        </div>
    </form>
@endsection
