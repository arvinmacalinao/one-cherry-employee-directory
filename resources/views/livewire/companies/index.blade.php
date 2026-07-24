<div class="flex flex-col gap-5">
    <div class="flex items-center gap-2.5">
        <div class="flex max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search companies…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="text-xs text-ink-tertiary">{{ $companies->count() }} compan{{ $companies->count() === 1 ? 'y' : 'ies' }}</span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($companies as $company)
            <a href="{{ route('companies.show', $company) }}" wire:navigate class="card flex flex-col overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-raised">
                <div class="relative h-16" style="background: linear-gradient(135deg, {{ $company->color_theme ?? '#790002' }}, #1c1c1e)">
                    <span class="absolute -bottom-5 left-5 flex h-13 w-13 items-center justify-center rounded-2xl border-4 border-surface-raised font-display text-sm font-bold text-white shadow-raised" style="background: {{ $company->color_theme ?? '#790002' }}">
                        {{ collect(explode(' ', $company->name))->map(fn ($w) => $w[0])->take(3)->implode('') }}
                    </span>
                </div>
                <div class="flex flex-col gap-2.5 px-5 pt-8 pb-5">
                    <div>
                        <p class="font-display text-sm font-bold">{{ $company->name }}</p>
                        <p class="text-xs text-ink-secondary">{{ $company->employees_count }} employee{{ $company->employees_count === 1 ? '' : 's' }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5 text-xs text-ink-secondary">
                        @if ($company->address)
                            <div class="flex items-start gap-2"><i class="fa-solid fa-location-dot mt-0.5 w-3.5 text-ink-tertiary"></i><span>{{ $company->address }}</span></div>
                        @endif
                        @if ($company->phone)
                            <div class="flex items-center gap-2"><i class="fa-solid fa-phone w-3.5 text-ink-tertiary"></i><span>{{ $company->phone }}</span></div>
                        @endif
                        @if ($company->email)
                            <div class="flex items-center gap-2"><i class="fa-solid fa-envelope w-3.5 text-ink-tertiary"></i><span class="truncate">{{ $company->email }}</span></div>
                        @endif
                    </div>
                    <span class="btn-secondary mt-1 justify-center !py-1.5 text-xs">View Company</span>
                </div>
            </a>
        @empty
            <div class="col-span-full">
                @include('livewire.partials.coming-soon', [
                    'icon' => 'fa-building',
                    'title' => 'No companies match',
                    'description' => 'Try a different search term.',
                ])
            </div>
        @endforelse
    </div>
</div>
