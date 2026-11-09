@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'autocomplete' => null,
    'readonly' => false,
])
<div class="field">
    <label for="{{ $name }}">{{ $label }}@unless ($required) <span class="optional">(optional)</span>@endunless</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @readonly($readonly)
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @class(['is-invalid' => $errors->has($name)])
        @if ($hint || $errors->has($name)) aria-describedby="{{ $name }}-help" @endif
        {{ $attributes }}
    >
    <div id="{{ $name }}-help">
        @if ($hint)
            <p class="hint">{{ $hint }}</p>
        @endif
        @error($name)
            <p class="error" role="alert">{{ $message }}</p>
        @enderror
    </div>
</div>
