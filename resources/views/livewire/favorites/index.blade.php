<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($favorites as $employee)
        <div class="card flex items-center gap-3 p-4">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-sm font-bold text-brand">
                {{ collect(explode(' ', $employee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
            </span>
            <div class="min-w-0 flex-1">
                <a href="{{ route('directory.show', $employee) }}" wire:navigate class="truncate font-display text-sm font-bold hover:text-brand">{{ $employee->full_name }}</a>
                <p class="truncate text-xs text-ink-secondary">{{ $employee->designation->name }}</p>
            </div>
            <button wire:click="toggle({{ $employee->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg text-amber-400 hover:bg-surface">
                <i class="fa-solid fa-star"></i>
            </button>
        </div>
    @empty
        <div class="col-span-full">
            @include('livewire.partials.coming-soon', [
                'icon' => 'fa-star',
                'title' => 'No favorites yet',
                'description' => 'Tap the star on any employee card or profile to pin them here for quick access.',
            ])
        </div>
    @endforelse
</div>
