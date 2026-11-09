@php($tabs = \App\Support\Navigation\SettingsTabs::visible(auth()->user()))
<div class="page-header">
    <div>
        <h1>Settings</h1>
    </div>
</div>
<nav class="tabs" aria-label="Settings sections">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}" @if ($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
