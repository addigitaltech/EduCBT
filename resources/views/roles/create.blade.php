@extends('layouts.app')

@section('title', 'Create role')

@section('content')
    @include('partials.settings-tabs')

    <div class="page-header">
        <div>
            <h2>Create role</h2>
            <p>Choose a clear name and what people with this role may do.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('roles.store') }}" class="card stack" style="max-width: 40rem" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-field name="name" label="Role name" required autocomplete="off" />
        <x-field name="description" label="Short description" autocomplete="off" />

        @include('roles._permissions', ['groups' => $groups, 'granted' => [], 'yours' => $yours, 'readonly' => false])

        <div class="row">
            <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Creating...' : 'Create role'">Create role</span></button>
            <a class="btn btn-secondary" href="{{ route('roles.index') }}">Cancel</a>
        </div>
    </form>
@endsection
