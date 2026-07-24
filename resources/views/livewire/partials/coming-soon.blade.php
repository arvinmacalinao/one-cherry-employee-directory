<div class="card flex flex-col items-center gap-3 px-6 py-16 text-center">
    <div class="flex h-13 w-13 items-center justify-center rounded-full border border-line bg-surface">
        <i class="fa-solid {{ $icon ?? 'fa-hammer' }} text-lg text-ink-tertiary"></i>
    </div>
    <h3 class="font-display text-base">{{ $title }}</h3>
    <p class="max-w-sm text-sm text-ink-secondary">{{ $description }}</p>
</div>
