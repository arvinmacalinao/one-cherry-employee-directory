<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by user…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <select wire:model.live="event" class="input max-w-[180px]">
            <option value="">All Actions</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="deleted">Deleted</option>
        </select>
        <span class="ml-auto text-xs text-ink-tertiary">{{ $logs->total() }} entries</span>
    </div>

    <div class="card divide-y divide-line px-5">
        @forelse ($logs as $log)
            <div x-data="{ open: false }" class="py-1">
                <button type="button" @click="open = !open" class="flex w-full items-center gap-3 py-3 text-left">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-surface text-ink-secondary">
                        <i class="fa-solid {{ $log->user ? 'fa-user' : 'fa-database' }} text-xs"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm"><strong class="font-semibold">{{ $log->user->name ?? 'System' }}</strong> {{ $log->event }} {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                        <span class="block text-xs text-ink-tertiary">{{ $log->created_at->format('M j, Y g:i A') }}</span>
                    </span>
                    <span class="badge {{ $log->event === 'created' ? 'badge-active' : ($log->event === 'deleted' ? 'badge-resigned' : 'bg-brand-tint text-brand') }}">{{ $log->event }}</span>
                    @if ($log->new_values || $log->old_values)
                        <i class="fa-solid fa-chevron-right text-xs text-ink-tertiary transition-transform duration-200" :class="open ? 'rotate-90' : ''"></i>
                    @else
                        <span class="w-3.5"></span>
                    @endif
                </button>

                @if ($log->new_values || $log->old_values)
                    <div x-show="open" x-cloak class="grid max-w-xl grid-cols-2 gap-2.5 py-2 pb-4 pl-11">
                        <div class="rounded-lg bg-danger-tint p-2.5 text-xs text-danger">
                            <span class="mb-1 block text-[10px] font-bold tracking-wide uppercase">Before</span>
                            @forelse (($log->old_values ?? []) as $key => $value)
                                <div class="line-through opacity-80"><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                            @empty
                                <span class="opacity-60">—</span>
                            @endforelse
                        </div>
                        <div class="rounded-lg bg-success-tint p-2.5 text-xs text-success">
                            <span class="mb-1 block text-[10px] font-bold tracking-wide uppercase">After</span>
                            @forelse (($log->new_values ?? []) as $key => $value)
                                <div><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                            @empty
                                <span class="opacity-60">—</span>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-ink-secondary">No matching entries.</p>
        @endforelse
    </div>

    <div>{{ $logs->links() }}</div>
</div>
