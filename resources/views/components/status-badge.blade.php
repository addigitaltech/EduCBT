@props(['status'])
@php
    $map = [
        'ok' => ['badge--ok', 'circle-check', 'Working'],
        'warning' => ['badge--warn', 'triangle-alert', 'Needs attention'],
        'problem' => ['badge--bad', 'circle-x', 'Problem'],
    ];
    [$class, $icon, $text] = $map[$status] ?? ['badge--neutral', 'circle', ucfirst($status)];
@endphp
<span class="badge {{ $class }}"><i data-lucide="{{ $icon }}" class="icon" style="width:1rem;height:1rem" aria-hidden="true"></i>{{ $text }}</span>
