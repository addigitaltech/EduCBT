@extends('layouts.app')

@section('title', $role->name)

@section('content')
    @include('partials.settings-tabs')

    @php($canManage = auth()->user()->can('roles.manage'))

    <div class="page-header">
        <div>
            <h2>{{ $role->name }}</h2>
            <p>{{ $role->description }} {{ $userCount }} {{ \Illuminate\Support\Str::plural('person', $userCount) }} {{ $userCount === 1 ? 'has' : 'have' }} this role.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('roles.index') }}">Back to roles</a>
    </div>

    <div class="stack-lg" style="max-width: 40rem">
        @if ($locked)
            <div class="alert alert--info" role="note">
                <i data-lucide="shield-check" class="icon" aria-hidden="true"></i>
                <div>This role always has full access, so it cannot be changed. This makes sure the installation can never lose its administrator.</div>
            </div>
        @endif

        @error('role')
            <div class="alert alert--bad" role="alert"><div>{{ $message }}</div></div>
        @enderror

        <form method="POST" action="{{ route('roles.update', $role) }}" class="card stack" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')

            @include('roles._permissions', ['groups' => $groups, 'granted' => $granted, 'yours' => $yours, 'readonly' => $locked || ! $canManage])

            @if ($canManage && ! $locked)
                <div class="row">
                    <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Saving...' : 'Save changes'">Save changes</span></button>
                </div>
            @endif
        </form>

        @if ($canManage && ! $role->is_system)
            <section class="card stack">
                <h2>Delete role</h2>
                <p class="muted">Deleting a role cannot be undone. A role that is still given to people cannot be deleted.</p>
                <form method="POST" action="{{ route('roles.destroy', $role) }}"
                      x-data @submit="if (! confirm('Delete the role {{ addslashes($role->name) }}? This cannot be undone.')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i data-lucide="trash-2" class="icon" aria-hidden="true"></i><span>Delete role</span></button>
                </form>
            </section>
        @endif
    </div>
@endsection
