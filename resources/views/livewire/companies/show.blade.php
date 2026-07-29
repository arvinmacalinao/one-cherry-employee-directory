<div class="flex flex-col gap-5">
    <div>
        <a href="{{ route('companies.index') }}" wire:navigate class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-ink-secondary hover:text-ink">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> All Companies
        </a>
        <div class="flex flex-wrap items-end gap-4 px-1">
            @if ($company->getFirstMediaUrl('logo', 'thumb'))
                <img src="{{ $company->getFirstMediaUrl('logo', 'thumb') }}" alt="{{ $company->name }}" class="h-18 w-18 flex-shrink-0 rounded-2xl border border-line object-cover shadow-raised">
            @else
                <span class="flex h-18 w-18 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-tint font-display text-lg font-bold text-brand shadow-raised">
                    {{ collect(explode(' ', $company->name))->map(fn ($w) => $w[0])->take(3)->implode('') }}
                </span>
            @endif
            <div class="flex-1 pb-1">
                <h2 class="font-display text-xl">{{ $company->name }}</h2>
                <p class="text-sm text-ink-secondary">{{ $employeeCount }} employees · {{ $departmentCount }} departments</p>
            </div>
        </div>
    </div>

    <div class="flex gap-1 border-b border-line">
        @foreach (['overview' => 'Overview', 'departments' => 'Departments', 'employees' => 'Employees'] as $key => $label)
            <button
                wire:click="setTab('{{ $key }}')"
                class="relative top-px border-b-2 px-1 py-2.5 mr-5 text-sm font-semibold transition-colors duration-200 {{ $tab === $key ? 'border-brand text-brand' : 'border-transparent text-ink-secondary hover:text-ink' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="card flex flex-col gap-5 p-6">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-ink-tertiary">Headquarters</dt><dd class="text-sm font-medium">{{ $company->address ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Phone</dt><dd class="text-sm font-medium">{{ $company->phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Email</dt><dd class="text-sm font-medium">{{ $company->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Website</dt><dd class="text-sm font-medium">{{ $company->website ?: '—' }}</dd></div>
            </dl>
        </div>
    @endif

    @if ($tab === 'departments')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($departments as $department)
                <div class="card flex flex-col gap-3 p-5">
                    <p class="font-display text-sm font-bold">{{ $department->name }}</p>
                    <a href="{{ route('directory.index') }}?department={{ $department->id }}" wire:navigate class="btn-ghost mt-1 !px-0 text-xs">
                        View Members <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            @empty
                <p class="col-span-full py-8 text-center text-sm text-ink-secondary">No departments on file yet.</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'employees')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($employees as $person)
                <a href="{{ route('directory.show', $person) }}" wire:navigate class="card flex items-center gap-3 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-raised">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint text-xs font-bold text-brand">
                        {{ collect(explode(' ', $person->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $person->full_name }}</span>
                        <span class="block truncate text-xs text-ink-secondary">{{ $person->designation?->name }}</span>
                    </span>
                </a>
            @empty
                <p class="col-span-full py-8 text-center text-sm text-ink-secondary">No employees found.</p>
            @endforelse
        </div>
        <div>{{ $employees->links() }}</div>
    @endif
</div>
