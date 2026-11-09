@extends('layouts.app')

@section('title', 'System health')

@section('content')
    <div class="page-header">
        <div>
            <h1>System health</h1>
            <p>Plain-language checks of this {{ strtolower($profileLabel ?? 'installation') }}. Technical support can use these results to find problems.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('system.health') }}"><i data-lucide="refresh-cw" class="icon" aria-hidden="true"></i><span>Check again</span></a>
    </div>

    <div class="card card--flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th scope="col">Check</th><th scope="col">Status</th><th scope="col">What it means</th></tr>
                </thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td data-label="Check"><strong>{{ $check['label'] }}</strong></td>
                            <td data-label="Status"><x-status-badge :status="$check['status']" /></td>
                            <td data-label="What it means">{{ $check['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="muted" style="margin-top: 1rem">Version {{ config('profile.version') }}</p>
@endsection
