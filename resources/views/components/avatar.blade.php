@props(['employee', 'size' => 'h-11.5 w-11.5', 'textSize' => 'text-sm', 'conversion' => 'thumb'])
@php
    $photoUrl = $employee->getFirstMediaUrl('photo', $conversion);
    $initials = collect(explode(' ', $employee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('');
@endphp
@if ($photoUrl)
    <img
        src="{{ $photoUrl }}"
        alt="{{ $employee->full_name }}"
        {{ $attributes->merge(['class' => "$size flex-shrink-0 rounded-full object-cover"]) }}
    >
@else
    <span {{ $attributes->merge(['class' => "flex $size flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display $textSize font-bold text-brand"]) }}>
        {{ $initials }}
    </span>
@endif
