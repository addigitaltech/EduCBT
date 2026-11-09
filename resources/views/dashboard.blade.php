@extends('layouts.app')

@section('title', $heading)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $heading }}</h1>
            <p>{{ $intro }}</p>
        </div>
    </div>

    <div class="stack-lg">
        <section class="card">
            <h2>System status</h2>
            <p class="row" style="margin-top: 0.5rem">
                <x-status-badge :status="$overall" />
                <span class="muted">
                    @if ($overall === 'ok') Everything checked is working.
                    @elseif ($overall === 'warning') Something needs attention.
                    @else Something is not working and needs fixing. @endif
                </span>
            </p>
            @can('system.health.view')
                <p style="margin-top: 0.75rem"><a href="{{ route('system.health') }}">See system health details</a></p>
            @endcan
        </section>

        @canany(['users.manage', 'roles.view', 'settings.view'])
            <section class="card">
                <h2>Quick actions</h2>
                <div class="row" style="margin-top: 0.75rem">
                    @can('users.manage')
                        <a class="btn btn-primary" href="{{ route('users.create') }}"><i data-lucide="user-plus" class="icon" aria-hidden="true"></i><span>Add user</span></a>
                    @endcan
                    @can('roles.view')
                        <a class="btn btn-secondary" href="{{ route('roles.index') }}"><i data-lucide="shield-check" class="icon" aria-hidden="true"></i><span>Roles</span></a>
                    @endcan
                    @can('settings.view')
                        <a class="btn btn-secondary" href="{{ route('settings.security') }}"><i data-lucide="lock" class="icon" aria-hidden="true"></i><span>Security settings</span></a>
                    @endcan
                </div>
            </section>
        @endcanany
    </div>
@endsection
