@extends('layouts.guest')

@section('title', 'Forgot your password')

@section('content')
    <div class="stack">
        <h2>Forgot your password?</h2>
        <p>Ask your administrator to reset it. They will give you a temporary password, and you will choose a new one when you sign in.</p>
        <p>If you are the administrator and cannot sign in, contact your technical support.</p>
        <a class="btn btn-secondary" href="{{ route('login') }}">Back to sign in</a>
    </div>
@endsection
