{{-- Expects: $groups, $granted (keys already allowed), $yours (keys the signed-in person holds), $readonly --}}
@error('permissions')
    <div class="alert alert--bad" role="alert"><div>{{ $message }}</div></div>
@enderror

@foreach ($groups as $group => $permissions)
    <fieldset>
        <legend>{{ $group }}</legend>
        @foreach ($permissions as $key => $description)
            @php
                $isGranted = in_array($key, old('permissions', $granted), true);
                $canGive = in_array($key, $yours, true);
                $disabled = $readonly || ! $canGive;
            @endphp
            <label class="check">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked($isGranted) @disabled($disabled)>
                <span>{{ $description }}@if (! $readonly && ! $canGive)<small>You cannot change this because you do not have it yourself.</small>@endif</span>
            </label>
            @if ($disabled && $isGranted && ! $readonly)
                {{-- Keep permissions you are not allowed to change, so saving does not remove them. --}}
                <input type="hidden" name="permissions[]" value="{{ $key }}">
            @endif
        @endforeach
    </fieldset>
@endforeach
