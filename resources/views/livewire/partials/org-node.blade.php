@php $depth = $depth ?? 0; @endphp
<div class="relative {{ $depth > 0 ? 'ml-6 border-l border-line pl-5' : '' }}">
    <div class="flex items-center gap-3 py-2">
        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-xs font-bold text-brand">
            {{ collect(explode(' ', $employee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
        </span>
        <a href="{{ route('directory.show', $employee) }}" wire:navigate class="min-w-0 hover:text-brand">
            <p class="truncate text-sm font-semibold">{{ $employee->full_name }}</p>
            <p class="truncate text-xs text-ink-secondary">{{ $employee->designation->name }}{{ $employee->department ? ' · '.$employee->department->name : '' }}</p>
        </a>
    </div>

    @if ($depth < 6 && $reportsMap->has($employee->id))
        <div class="flex flex-col">
            @foreach ($reportsMap->get($employee->id) as $report)
                @include('livewire.partials.org-node', ['employee' => $report, 'reportsMap' => $reportsMap, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
