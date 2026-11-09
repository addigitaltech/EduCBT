@extends('layouts.app')

@section('title', 'Appearance')

@section('content')
    @include('partials.settings-tabs')

    @php($canManage = auth()->user()->can('settings.manage'))

    <div class="page-header">
        <div>
            <h2>Appearance</h2>
            <p>The name and colour people see. All colours keep text easy to read.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.appearance.update') }}" class="card stack" style="max-width: 36rem" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @method('PUT')

        <x-field name="display_name" label="Name shown at the top" :value="$values['display_name']" :readonly="! $canManage"
                 hint="Leave empty to use the product name. School branding such as the logo arrives with school setup." />

        <fieldset>
            <legend>Accent colour</legend>
            @foreach ($accents as $key => $label)
                <label class="check">
                    <input type="radio" name="accent" value="{{ $key }}" @checked(old('accent', $values['accent']) === $key) @disabled(! $canManage)>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
            @error('accent')<p class="error" role="alert">{{ $message }}</p>@enderror
        </fieldset>

        @if ($canManage)
            <div class="row">
                <button type="submit" class="btn btn-primary" :disabled="busy"><span x-text="busy ? 'Saving...' : 'Save changes'">Save changes</span></button>
            </div>
        @endif
    </form>
@endsection
