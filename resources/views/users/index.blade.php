@extends('layouts.app')

@section('title', 'Users')

@section('content')
    @include('partials.settings-tabs')

    <div class="page-header">
        <div>
            <h2>Users</h2>
            <p>People who can sign in to manage this {{ strtolower($profileLabel ?? 'installation') }}.</p>
        </div>
        @can('users.manage')
            <a class="btn btn-primary" href="{{ route('users.create') }}"><i data-lucide="user-plus" class="icon" aria-hidden="true"></i><span>Add user</span></a>
        @endcan
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="card grid-2" style="margin-bottom: 1rem" role="search">
        <div class="field">
            <label for="q">Search by name or sign-in ID</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (\App\Domain\Identity\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="row">
            <button type="submit" class="btn btn-primary"><i data-lucide="search" class="icon" aria-hidden="true"></i><span>Apply filters</span></button>
            @if (! empty($filters['q']) || ! empty($filters['status']))
                <a class="btn btn-secondary" href="{{ route('users.index') }}">Clear filters</a>
            @endif
        </div>
    </form>

    <div class="card card--flush">
        @if ($users->isEmpty())
            <div class="empty">
                <h2>No users found</h2>
                <p>{{ empty($filters['q']) && empty($filters['status']) ? 'Add the first user to get started.' : 'No one matches these filters. Try a different search.' }}</p>
                @can('users.manage')
                    <a class="btn btn-primary" href="{{ route('users.create') }}">Add user</a>
                @endcan
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Roles</th>
                            <th scope="col">Status</th>
                            <th scope="col">Last sign-in</th>
                            <th scope="col"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td data-label="Name"><strong>{{ $user->name }}</strong><span class="sub">{{ $user->login_id }}</span></td>
                                <td data-label="Roles">{{ $user->roles->pluck('name')->join(', ') ?: 'No role' }}</td>
                                <td data-label="Status">
                                    @php($ok = $user->status === \App\Domain\Identity\Enums\UserStatus::Active)
                                    <span class="badge {{ $ok ? 'badge--ok' : 'badge--neutral' }}">{{ $user->status->label() }}</span>
                                </td>
                                <td data-label="Last sign-in">{{ $user->last_login_at?->timezone(config('profile.display_timezone'))->format('d M Y, H:i') ?? 'Never' }}</td>
                                <td data-label="Action"><a href="{{ route('users.edit', $user) }}">{{ auth()->user()->can('users.manage') ? 'Edit' : 'View' }}<span class="sr-only"> {{ $user->name }}</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $users->links('partials.pagination') }}
@endsection
