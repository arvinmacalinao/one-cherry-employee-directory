<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search departments…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="ml-auto text-xs text-ink-tertiary">{{ $departments->count() }} department{{ $departments->count() === 1 ? '' : 's' }}</span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($departments as $department)
            <div class="card flex flex-col gap-3 p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-raised">
                <p class="font-display text-sm font-bold">{{ $department->name }}</p>
                <div class="flex items-center justify-between border-t border-line pt-3">
                    <span class="font-display text-lg font-bold tabular-nums">{{ $department->employees_count }}</span>
                    <a href="{{ route('directory.index') }}?department={{ $department->id }}" wire:navigate class="btn-ghost !px-0 text-xs">
                        View Members <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                @include('livewire.partials.coming-soon', [
                    'icon' => 'fa-sitemap',
                    'title' => 'No departments match',
                    'description' => 'Try a different search term.',
                ])
            </div>
        @endforelse
    </div>
</div>
