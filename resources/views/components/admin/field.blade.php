@props(['label', 'locked' => false, 'error' => null, 'full' => false])
<div class="flex flex-col gap-1.5 {{ $full ? 'sm:col-span-2' : '' }}">
    <label class="flex items-center gap-1.5 text-xs font-semibold text-ink-secondary">
        @if ($locked) <i class="fa-solid fa-lock text-[10px] text-ink-tertiary"></i> @endif
        {{ $label }}
    </label>
    {{ $slot }}
    @if ($locked)
        <span class="flex items-center gap-1 text-[10px] text-ink-tertiary"><i class="fa-solid fa-lock text-[9px]"></i> Synced from HR</span>
    @elseif ($error)
        <span class="text-[11px] font-medium text-danger">{{ $error }}</span>
    @endif
</div>
