@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    @include('partials.settings-tabs')

    <div class="page-header">
        <div>
            <h2>Audit log</h2>
            <p>A permanent record of important actions. Entries cannot be changed or deleted.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="card grid-2" style="margin-bottom: 1rem" role="search">
        <div class="field">
            <label for="action">Action contains</label>
            <input id="action" type="search" name="action" value="{{ $filters['action'] ?? '' }}">
        </div>
        <div class="field">
            <label for="entity_type">Type of record</label>
            <select id="entity_type" name="entity_type">
                <option value="">All</option>
                @foreach ($entityTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['entity_type'] ?? '') === $type)>{{ \Illuminate\Support\Str::headline($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">From date</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">To date</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" @class(['is-invalid' => $errors->has('to')])>
            @error('to')<p class="error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="row">
            <button type="submit" class="btn btn-primary"><i data-lucide="search" class="icon" aria-hidden="true"></i><span>Apply filters</span></button>
            <a class="btn btn-secondary" href="{{ route('audit.index') }}">Clear filters</a>
        </div>
    </form>

    <div class="card card--flush">
        @if ($logs->isEmpty())
            <div class="empty">
                <h2>No entries found</h2>
                <p>Nothing matches these filters yet.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">What happened</th>
                            <th scope="col">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td data-label="When">{{ $log->occurred_at->timezone(config('profile.display_timezone'))->format('d M Y, H:i:s') }}</td>
                                <td data-label="Who">{{ $log->actor_label ?? 'Not signed in' }}</td>
                                <td data-label="What happened">{{ \Illuminate\Support\Str::of($log->action)->replace(['.', '_'], ' ')->ucfirst() }}</td>
                                <td data-label="Details">
                                    <details>
                                        <summary>Show details</summary>
                                        <p class="sub">Record: {{ \Illuminate\Support\Str::headline($log->entity_type) }}{{ $log->entity_uuid ? ' '.$log->entity_uuid : '' }}</p>
                                        <p class="sub">Address: {{ $log->ip_address ?? 'unknown' }} &middot; Request: {{ $log->request_id ?? 'none' }}</p>
                                        @if ($log->before_json)
                                            <p class="sub">Before: <code>{{ json_encode($log->before_json, JSON_UNESCAPED_UNICODE) }}</code></p>
                                        @endif
                                        @if ($log->after_json)
                                            <p class="sub">After: <code>{{ json_encode($log->after_json, JSON_UNESCAPED_UNICODE) }}</code></p>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $logs->links('partials.pagination') }}
@endsection
