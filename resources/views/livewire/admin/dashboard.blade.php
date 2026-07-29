<div class="flex flex-col gap-6" x-data @sync-complete.window="$el.querySelector('#sync-toast')?.classList.remove('hidden')">

    @if ($unmappedCount > 0)
        <a href="{{ route('admin.sync') }}" wire:navigate class="card flex items-center gap-3.5 bg-warning-tint p-4 transition-shadow hover:shadow-raised">
            <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold">{{ $unmappedCount }} record{{ $unmappedCount === 1 ? '' : 's' }} need{{ $unmappedCount === 1 ? 's' : '' }} your review</p>
                <p class="text-xs text-ink-secondary">The last HR sync found IDs it couldn't match to an existing department, designation, or company.</p>
            </div>
            <i class="fa-solid fa-chevron-right text-ink-tertiary"></i>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="card flex flex-col gap-2.5 p-5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid fa-users text-sm"></i></div>
            <div class="font-display text-2xl font-bold tabular-nums">{{ $employeeCount }}</div>
            <div class="text-xs font-medium text-ink-secondary">Total Employees</div>
        </div>
        <div class="card flex flex-col gap-2.5 p-5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $unmappedCount > 0 ? 'bg-warning-tint text-warning' : 'bg-brand-tint text-brand' }}"><i class="fa-solid fa-triangle-exclamation text-sm"></i></div>
            <div class="font-display text-2xl font-bold tabular-nums">{{ $unmappedCount }}</div>
            <div class="text-xs font-medium text-ink-secondary">Unmapped Records</div>
        </div>
        <div class="card flex flex-col gap-2.5 p-5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid fa-building text-sm"></i></div>
            <div class="font-display text-2xl font-bold tabular-nums">{{ $companyCount }}</div>
            <div class="text-xs font-medium text-ink-secondary">Active Companies</div>
        </div>
        <div class="card flex flex-col gap-2.5 p-5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid fa-database text-sm"></i></div>
            <div class="font-display text-2xl font-bold capitalize">{{ $lastSync?->status?->value ?? '—' }}</div>
            <div class="text-xs font-medium text-ink-secondary">Last Sync Status</div>
        </div>
    </div>

    <div class="card p-5">
        <div class="mb-4 flex items-center justify-between">
            <p class="font-display text-sm font-bold">Sync health</p>
            <button wire:click="runSync" wire:loading.attr="disabled" class="btn-primary text-xs">
                <i wire:loading.remove wire:target="runSync" class="fa-solid fa-arrows-rotate"></i>
                <i wire:loading wire:target="runSync" class="fa-solid fa-arrows-rotate animate-spin"></i>
                <span wire:loading.remove wire:target="runSync">Run Sync Now</span>
                <span wire:loading wire:target="runSync">Syncing…</span>
            </button>
        </div>
        <div id="sync-toast" class="hidden mb-4 rounded-lg bg-success-tint px-3 py-2 text-xs font-semibold text-success">Sync complete — everything is up to date.</div>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ $lastSync?->records_imported ?? 0 }}</p><p class="text-xs text-ink-secondary">New Hires</p></div>
            <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ $lastSync?->records_promoted ?? 0 }}</p><p class="text-xs text-ink-secondary">Promotions</p></div>
            <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ $lastSync?->records_deactivated ?? 0 }}</p><p class="text-xs text-ink-secondary">Deactivated</p></div>
            <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums {{ ($lastSync?->errors ? count($lastSync->errors) : 0) > 0 ? 'text-danger' : '' }}">{{ $lastSync?->errors ? count($lastSync->errors) : 0 }}</p><p class="text-xs text-ink-secondary">Warnings</p></div>
        </div>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-bold tracking-wide text-ink-tertiary uppercase">Recent audit activity</p>
            <a href="{{ route('admin.audit.index') }}" wire:navigate class="text-xs font-semibold text-brand">View all <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
        </div>
        <div class="card divide-y divide-line px-4">
            @forelse ($recentAudits as $audit)
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-surface text-ink-secondary">
                        <i class="fa-solid {{ $audit->user ? 'fa-user' : 'fa-database' }} text-xs"></i>
                    </span>
                    <div class="min-w-0 flex-1 text-sm">
                        <strong class="font-semibold">{{ $audit->user->name ?? 'System' }}</strong>
                        {{ $audit->event }} {{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}
                    </div>
                    <span class="flex-shrink-0 text-xs text-ink-tertiary">{{ $audit->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-ink-secondary">No audit activity yet.</p>
            @endforelse
        </div>
    </div>

    <div>
        <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Manage</p>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            @foreach ($quickLinks as $link)
                <a href="{{ route($link['route']) }}" wire:navigate class="card flex flex-col gap-2.5 p-5 text-left transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-raised">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid {{ $link['icon'] }} text-sm"></i></div>
                    <div class="font-display text-2xl font-bold tabular-nums">{{ $link['count'] }}</div>
                    <div class="text-xs font-medium text-ink-secondary">{{ $link['label'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
