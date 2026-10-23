<div class="flex flex-col gap-6 print:gap-3">

    {{-- Search — looks across every building at once --}}
    <div class="rounded-panel border border-line bg-gradient-to-b from-surface to-bg px-5 py-8 text-center sm:px-8 print:hidden">
        <h2 class="mb-1.5 font-display text-2xl">Local Phone Numbers</h2>
        <p class="mb-5 text-sm text-ink-secondary">Search by name, department or local number.</p>

        <div class="mx-auto flex max-w-xl items-center gap-2.5 rounded-2xl border-[1.5px] border-line bg-surface-raised px-5 py-3.5 shadow-raised focus-within:border-brand focus-within:ring-4 focus-within:ring-brand-tint">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input
                wire:model.live.debounce.250ms="search"
                type="search"
                placeholder="e.g. Accounting, Jasmine, 1820…"
                class="w-full border-0 bg-transparent text-sm outline-none"
                autocomplete="off"
                autofocus
            >
            @if ($searching)
                <button type="button" wire:click="$set('search', '')" class="text-ink-tertiary hover:text-ink" title="Clear search"><i class="fa-solid fa-xmark"></i></button>
            @endif
        </div>
    </div>

    @if ($buildings->isEmpty())
        <div class="card px-6 py-14 text-center text-sm text-ink-secondary">
            <i class="fa-solid fa-phone mb-3 text-2xl text-ink-tertiary"></i>
            <p>The phone directory hasn't been set up yet.</p>
        </div>
    @elseif ($searching)
        <div class="flex items-center gap-3 print:hidden">
            <p class="text-sm text-ink-secondary">
                <strong class="text-ink">{{ $resultCount }}</strong> {{ Str::plural('line', $resultCount) }} matching “{{ trim($search) }}” across all buildings
            </p>
        </div>

        @forelse ($floors->groupBy('phone_building_id') as $buildingFloors)
            <section class="flex flex-col gap-3">
                <h3 class="flex items-center gap-2 font-display text-base"><i class="fa-solid fa-building text-sm text-brand"></i>{{ $buildingFloors->first()->building->name }}</h3>
                @include('livewire.partials.phone-sheet', ['floors' => $buildingFloors])
            </section>
        @empty
            <div class="card px-6 py-12 text-center text-sm text-ink-secondary">
                No lines match “{{ trim($search) }}”. Try a first name, a department, or the local number.
            </div>
        @endforelse
    @else
        {{-- Building tabs --}}
        <div class="flex flex-wrap items-center gap-2.5 print:hidden">
            <div class="flex flex-wrap gap-1 rounded-control border border-line bg-surface p-1">
                @foreach ($buildings as $tab)
                    <button
                        type="button"
                        wire:click="selectBuilding({{ $tab->id }})"
                        @class([
                            'rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors duration-200',
                            'bg-brand text-on-brand shadow-card' => $tab->id === $building->id,
                            'text-ink-secondary hover:bg-surface-raised hover:text-ink' => $tab->id !== $building->id,
                        ])
                    >{{ $tab->name }}</button>
                @endforeach
            </div>
            <button type="button" onclick="window.print()" class="btn-secondary ml-auto text-xs"><i class="fa-solid fa-print"></i>Print {{ $building->name }}</button>
        </div>

        {{-- Sheet title, as on the printout --}}
        <div class="text-center">
            <p class="font-display text-lg font-bold tracking-wide uppercase print:text-[14pt]">Local Phone Numbers</p>
            <p class="text-xs font-semibold tracking-[0.2em] text-ink-secondary uppercase print:text-[8pt]">{{ $building->name }}</p>
        </div>

        @if ($floors->isEmpty())
            <div class="card px-6 py-12 text-center text-sm text-ink-secondary">No floors have been added to {{ $building->name }} yet.</div>
        @else
            @include('livewire.partials.phone-sheet', ['floors' => $floors])
        @endif

        @if ($building->trunkline || $building->mobile || $building->last_updated_on)
            <div class="flex flex-wrap justify-end gap-x-6 gap-y-1 text-xs font-semibold tracking-wide text-ink-secondary uppercase print:text-[8pt] print:text-ink">
                @if ($building->trunkline)
                    <span>Trunkline: <span class="text-ink tabular-nums">{{ $building->trunkline }}</span></span>
                @endif
                @if ($building->mobile)
                    <span>Mobile: <span class="text-ink tabular-nums">{{ $building->mobile }}</span></span>
                @endif
                @if ($building->last_updated_on)
                    <span>Last update: <span class="text-ink">{{ $building->last_updated_on->format('F j, Y') }}</span></span>
                @endif
            </div>
        @endif
    @endif
</div>
