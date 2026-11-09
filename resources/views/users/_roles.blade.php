<fieldset>
    <legend>Roles</legend>
    <p class="hint">Roles decide what this person can do. You can only give roles that allow no more than you can do yourself.</p>
    @foreach ($roles as $role)
        <label class="check">
            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, array_map('intval', (array) old('roles', $selected ?? []))))>
            <span>{{ $role->name }}<small>{{ $role->description }}</small></span>
        </label>
    @endforeach
    @error('roles')
        <p class="error" role="alert">{{ $message }}</p>
    @enderror
</fieldset>
