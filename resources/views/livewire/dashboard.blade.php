<div class="flex flex-col gap-8">

    {{-- Hero search --}}
    <div class="rounded-panel border border-line bg-gradient-to-b from-surface to-bg px-6 py-10 text-center sm:px-8">
        <p class="mb-2 text-xs font-bold tracking-wide text-brand uppercase">Good day, {{ explode(' ', auth()->user()->name)[0] }}</p>
        <h2 class="mb-5 font-display text-2xl">Who are you looking for?</h2>

        <div class="relative mx-auto max-w-xl" x-data @click.outside="$wire.showResults = false">
            <div class="flex items-center gap-2.5 rounded-2xl border-[1.5px] border-line bg-surface-raised px-5 py-3.5 shadow-raised focus-within:border-brand focus-within:ring-4 focus-within:ring-brand-tint">
                <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
                <input
                    wire:model.live.debounce.250ms="heroSearch"
                    type="text"
                    placeholder="Search by name, email, mobile, department or company…"
                    class="w-full border-0 bg-transparent text-sm outline-none"
                    autocomplete="off"
                >
            </div>

            @if ($showResults && $searchResults)
                <div class="absolute inset-x-0 top-full z-30 mt-2 max-h-96 overflow-y-auto rounded-card border border-line bg-surface-raised text-left shadow-popover">
                    @php $total = $searchResults['people']->count() + $searchResults['companies']->count() + $searchResults['departments']->count(); @endphp

                    @if ($total === 0)
                        <p class="p-5 text-center text-sm text-ink-secondary">No matches. Try a name, email, mobile number, department or company.</p>
                    @else
                        @if ($searchResults['people']->isNotEmpty())
                            <p class="px-4 pt-3 pb-1 text-[10px] font-bold tracking-wide text-ink-tertiary uppercase">People</p>
                            @foreach ($searchResults['people'] as $person)
                                <a href="{{ route('directory.show', $person) }}" wire:navigate class="flex items-center gap-3 px-4 py-2 hover:bg-surface">
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint text-xs font-bold text-brand">
                                        {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $person->full_name }}</span>
                                        <span class="block truncate text-xs text-ink-secondary">{{ $person->designation->name }} · {{ $person->company->name }}</span>
                                    </span>
                                </a>
                            @endforeach
                        @endif

                        @if ($searchResults['companies']->isNotEmpty())
                            <p class="px-4 pt-3 pb-1 text-[10px] font-bold tracking-wide text-ink-tertiary uppercase">Companies</p>
                            @foreach ($searchResults['companies'] as $company)
                                <a href="{{ route('companies.index') }}" wire:navigate class="flex items-center gap-3 px-4 py-2 hover:bg-surface">
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white" style="background: {{ $company->color_theme }}">{{ Str::of($company->name)->explode(' ')->map(fn ($w) => $w[0])->take(2)->implode('') }}</span>
                                    <span class="truncate text-sm font-semibold">{{ $company->name }}</span>
                                </a>
                            @endforeach
                        @endif

                        @if ($searchResults['departments']->isNotEmpty())
                            <p class="px-4 pt-3 pb-1 text-[10px] font-bold tracking-wide text-ink-tertiary uppercase">Departments</p>
                            @foreach ($searchResults['departments'] as $department)
                                <a href="{{ route('directory.index') }}?department={{ $department->id }}" wire:navigate class="flex items-center gap-3 px-4 py-2 hover:bg-surface">
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-surface text-ink-tertiary"><i class="fa-solid fa-sitemap text-xs"></i></span>
                                    <span class="truncate text-sm font-semibold">{{ $department->name }} <span class="font-normal text-ink-secondary">· {{ $department->company->name }}</span></span>
                                </a>
                            @endforeach
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Quick statistics --}}
    <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        @foreach ([
            ['icon' => 'fa-users', 'value' => $stats['employees'], 'label' => 'Total Employees'],
            ['icon' => 'fa-building', 'value' => $stats['companies'], 'label' => 'Companies'],
            ['icon' => 'fa-sitemap', 'value' => $stats['departments'], 'label' => 'Departments'],
            ['icon' => 'fa-location-dot', 'value' => $stats['office_locations'], 'label' => 'Office Locations'],
        ] as $tile)
            <div class="card flex flex-col gap-2.5 p-5 transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-raised">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid {{ $tile['icon'] }} text-sm"></i></div>
                <div class="font-display text-2xl font-bold tabular-nums">{{ $tile['value'] }}</div>
                <div class="text-xs font-medium text-ink-secondary">{{ $tile['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Quick access --}}
    <div>
        <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Quick access</p>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            @foreach ([
                ['route' => 'directory.index', 'icon' => 'fa-address-book', 'title' => 'Employee Directory', 'desc' => 'Browse and filter everyone in the Group.'],
                ['route' => 'companies.index', 'icon' => 'fa-building', 'title' => 'Company Directory', 'desc' => 'See every company under One Cherry Group.'],
                ['route' => 'departments.index', 'icon' => 'fa-sitemap', 'title' => 'Departments', 'desc' => 'Find teams and department heads.'],
                ['route' => 'profile.me', 'icon' => 'fa-id-badge', 'title' => 'My Profile', 'desc' => 'View and share your own contact card.'],
            ] as $card)
                <a href="{{ route($card['route']) }}" wire:navigate class="card flex flex-col gap-3 p-5 transition-all duration-200 hover:-translate-y-1 hover:shadow-raised">
                    <div class="flex h-9.5 w-9.5 items-center justify-center rounded-lg bg-surface text-brand"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div>
                        <p class="font-display text-sm font-bold">{{ $card['title'] }}</p>
                        <p class="text-xs text-ink-secondary">{{ $card['desc'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Birthday celebrants --}}
    @if ($birthdayCelebrants->isNotEmpty())
        <div>
            <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">🎂 Birthday celebrants</p>
            <div class="flex gap-3.5 overflow-x-auto pb-1">
                @foreach ($birthdayCelebrants as $person)
                    @php $isToday = $person->profile->birthday->format('m-d') === now()->format('m-d'); @endphp
                    <a href="{{ route('directory.show', $person) }}" wire:navigate class="card flex w-42 flex-shrink-0 flex-col items-center gap-2.5 p-4 text-center transition-transform duration-200 hover:-translate-y-1 hover:shadow-raised">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-tint font-display text-lg font-bold text-brand">
                            {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                        </span>
                        <div>
                            <p class="truncate text-sm font-semibold">{{ $person->full_name }}</p>
                            <p class="truncate text-xs text-ink-secondary">{{ $person->department->name }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isToday ? 'bg-brand text-on-brand' : 'bg-brand-tint text-brand' }}">
                            {{ $isToday ? '🎉 Today' : $person->profile->birthday->format('M j') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- New employees / Recently viewed / Favorites --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.3fr_1fr]">
        <div>
            <div class="mb-3 flex items-center justify-between">
                <p class="text-xs font-bold tracking-wide text-ink-tertiary uppercase">New employees</p>
                <a href="{{ route('directory.index') }}" wire:navigate class="text-xs font-semibold text-brand">View directory <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
            </div>
            <div class="card divide-y divide-line px-4">
                @forelse ($newHires as $person)
                    <a href="{{ route('directory.show', $person) }}" wire:navigate class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint text-xs font-bold text-brand">
                            {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $person->full_name }} <span class="rounded-full bg-success-tint px-1.5 py-0.5 text-[10px] font-bold text-success">NEW</span></span>
                            <span class="block truncate text-xs text-ink-secondary">{{ $person->designation->name }} · {{ $person->company->name }}</span>
                        </span>
                        <span class="flex-shrink-0 text-xs text-ink-tertiary">{{ $person->date_hired->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="py-6 text-sm text-ink-secondary">No new employees in the last 30 days.</p>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <div>
                <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Recently viewed</p>
                <div class="card divide-y divide-line px-4">
                    @forelse ($recentlyViewed as $person)
                        <a href="{{ route('directory.show', $person) }}" wire:navigate class="flex items-center gap-3 py-2.5">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint text-xs font-bold text-brand">
                                {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold">{{ $person->full_name }}</span>
                                <span class="block truncate text-xs text-ink-secondary">{{ $person->designation->name }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="py-6 text-sm text-ink-secondary">Employees you view will show up here.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Favorites</p>
                <div class="card divide-y divide-line px-4">
                    @forelse ($favorites as $person)
                        <a href="{{ route('directory.show', $person) }}" wire:navigate class="flex items-center gap-3 py-2.5">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint text-xs font-bold text-brand">
                                {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold">{{ $person->full_name }}</span>
                                <span class="block truncate text-xs text-ink-secondary">{{ $person->designation->name }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="py-6 text-sm text-ink-secondary">Tap the star on any profile to pin it here.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Announcements --}}
    @if ($announcements->isNotEmpty())
        <div>
            <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Announcements</p>
            <div class="flex flex-col gap-2.5">
                @foreach ($announcements as $announcement)
                    <div class="card flex items-start gap-3.5 p-4">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid {{ $announcement['icon'] }} text-sm"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">{{ $announcement['title'] }}</p>
                            <p class="text-xs text-ink-secondary">{{ $announcement['body'] }}</p>
                            <p class="mt-0.5 text-[11px] text-ink-tertiary">{{ $announcement['date'] }}</p>
                        </div>
                        <button wire:click="dismissAnnouncement({{ $announcement['id'] }})" class="flex-shrink-0 rounded-lg p-1.5 text-ink-tertiary hover:bg-surface hover:text-ink">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
