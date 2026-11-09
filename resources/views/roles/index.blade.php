@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    @include('partials.settings-tabs')

    <div class="page-header">
        <div>
            <h2>Roles</h2>
            <p>A role is a set of things a person is allowed to do. Give people roles from the Users tab.</p>
        </div>
        @can('roles.manage')
            <a class="btn btn-primary" href="{{ route('roles.create') }}"><i data-lucide="plus" class="icon" aria-hidden="true"></i><span>Create role</span></a>
        @endcan
    </div>

    <div class="card card--flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Role</th>
                        <th scope="col">Type</th>
                        <th scope="col">People</th>
                        <th scope="col">Permissions</th>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td data-label="Role"><strong>{{ $role->name }}</strong><span class="sub">{{ $role->description }}</span></td>
                            <td data-label="Type"><span class="badge badge--neutral">{{ $role->is_system ? 'Built-in' : 'Custom' }}</span></td>
                            <td data-label="People">{{ $role->users_count }}</td>
                            <td data-label="Permissions">{{ $role->permissions_count }}</td>
                            <td data-label="Action"><a href="{{ route('roles.edit', $role) }}">{{ auth()->user()->can('roles.manage') ? 'Edit' : 'View' }}<span class="sr-only"> {{ $role->name }}</span></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
