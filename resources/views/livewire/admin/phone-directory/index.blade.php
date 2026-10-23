<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    {{-- Building tabs --}}
    <div class="flex flex-wrap items-center gap-2.5">
        @if ($buildings->isNotEmpty())
            <div class="flex flex-wrap gap-1 rounded-control border border-line bg-surface p-1">
                @foreach ($buildings as $tab)
                    <button
                        type="button"
                        wire:click="selectBuilding({{ $tab->id }})"
                        @class([
                            'rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors duration-200',
                            'bg-brand text-on-brand shadow-card' => $tab->id === $building?->id,
                            'text-ink-secondary hover:bg-surface-raised hover:text-ink' => $tab->id !== $building?->id,
                        ])
                    >{{ $tab->name }}</button>
                @endforeach
            </div>
        @endif
        <a href="{{ route('home', ['building' => $building?->id]) }}" target="_blank" class="btn-ghost ml-auto text-xs"><i class="fa-solid fa-arrow-up-right-from-square"></i>View public page</a>
        <button wire:click="openCreateBuilding" class="btn-secondary text-xs"><i class="fa-solid fa-plus"></i>Add Building</button>
    </div>

    @if (! $building)
        <div class="card px-6 py-14 text-center text-sm text-ink-secondary">
            <i class="fa-solid fa-building mb-3 text-2xl text-ink-tertiary"></i>
            <p>No buildings yet. Add one to start the phone directory.</p>
        </div>
    @else
        {{-- Building summary --}}
        <div class="card flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4">
            <div class="min-w-0">
                <p class="font-display text-base font-bold">{{ $building->name }}</p>
                <p class="text-xs text-ink-secondary">
                    {{ $floors->count() }} {{ Str::plural('floor', $floors->count()) }} · {{ $floors->sum(fn ($f) => $f->entries->count()) }} lines
                </p>
            </div>
            <dl class="flex flex-wrap gap-x-6 gap-y-1 text-xs">
                <div><dt class="text-ink-tertiary">Trunkline</dt><dd class="font-semibold tabular-nums">{{ $building->trunkline ?: '—' }}</dd></div>
                <div><dt class="text-ink-tertiary">Mobile</dt><dd class="font-semibold tabular-nums">{{ $building->mobile ?: '—' }}</dd></div>
                <div><dt class="text-ink-tertiary">Last update</dt><dd class="font-semibold">{{ $building->last_updated_on?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
            <div class="ml-auto flex items-center gap-1">
                <button wire:click="moveBuilding({{ $building->id }}, -1)" @disabled($buildings->first()->id === $building->id) class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink disabled:opacity-30" title="Move tab left"><i class="fa-solid fa-arrow-left"></i></button>
                <button wire:click="moveBuilding({{ $building->id }}, 1)" @disabled($buildings->last()->id === $building->id) class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink disabled:opacity-30" title="Move tab right"><i class="fa-solid fa-arrow-right"></i></button>
                <button wire:click="openEditBuilding({{ $building->id }})" class="btn-secondary text-xs"><i class="fa-solid fa-pen"></i>Edit Building</button>
            </div>
        </div>

        <div class="flex items-start gap-2.5 rounded-lg bg-brand-tint px-3.5 py-3 text-xs text-ink-secondary">
            <i class="fa-solid fa-circle-info mt-0.5 text-brand"></i>
            <span>Starred lines (managers) are highlighted, like the shaded rows on the printed sheet. Consecutive lines with the same department (or the same local) are merged into one cell on the public page, just like the printed sheet. "Last update" moves to today whenever anything in this building changes.</span>
        </div>

        <datalist id="phone-departments">
            @foreach ($departmentOptions as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>

        {{-- Floors --}}
        @foreach ($floors as $floor)
            <div wire:key="floor-{{ $floor->id }}" class="overflow-hidden rounded-card border border-line bg-surface-raised">
                <div class="phone-sheet-floor flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2.5">
                    <p class="font-display text-sm font-bold tracking-wide uppercase">{{ $floor->name }}</p>
                    @if ($floor->access_code)
                        <span class="text-[11px] font-semibold tracking-wide uppercase opacity-80">Access code – {{ $floor->access_code }}</span>
                    @endif
                    <div class="ml-auto flex items-center gap-0.5">
                        <button wire:click="moveFloor({{ $floor->id }}, -1)" @disabled($loop->first) class="rounded-lg p-1.5 opacity-80 hover:bg-white/15 hover:opacity-100 disabled:opacity-30" title="Move floor up"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                        <button wire:click="moveFloor({{ $floor->id }}, 1)" @disabled($loop->last) class="rounded-lg p-1.5 opacity-80 hover:bg-white/15 hover:opacity-100 disabled:opacity-30" title="Move floor down"><i class="fa-solid fa-arrow-down text-xs"></i></button>
                        <button wire:click="openEditFloor({{ $floor->id }})" class="rounded-lg p-1.5 opacity-80 hover:bg-white/15 hover:opacity-100" title="Edit floor"><i class="fa-solid fa-pen text-xs"></i></button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                                <th class="w-[38%] px-4 py-2">Company / Department</th>
                                <th class="w-[32%] px-4 py-2">Name</th>
                                <th class="w-[14%] px-4 py-2">Local</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($floor->entries as $entry)
                                @if ($editingEntryId === $entry->id)
                                    @include('livewire.admin.phone-directory.entry-form', ['key' => "edit-{$entry->id}"])
                                @else
                                    <tr wire:key="entry-{{ $entry->id }}" @class(['border-b border-line last:border-b-0', 'phone-sheet-highlight' => $entry->is_highlighted, 'hover:bg-surface' => ! $entry->is_highlighted])>
                                        <td class="px-4 py-2 font-semibold">{{ $entry->department ?: '—' }}</td>
                                        <td class="px-4 py-2 text-ink-secondary">{{ $entry->name ?: '—' }}</td>
                                        <td class="px-4 py-2 font-bold whitespace-nowrap tabular-nums">{{ $entry->local ?: '—' }}</td>
                                        <td class="px-2 py-1 text-right whitespace-nowrap">
                                            <button wire:click="toggleHighlight({{ $entry->id }})" @class(['rounded-lg p-2 hover:bg-surface-raised', 'text-brand' => $entry->is_highlighted, 'text-ink-tertiary hover:text-ink' => ! $entry->is_highlighted]) title="{{ $entry->is_highlighted ? 'Remove highlight' : 'Highlight (managers)' }}"><i class="fa-{{ $entry->is_highlighted ? 'solid' : 'regular' }} fa-star text-xs"></i></button>
                                            <button wire:click="moveEntry({{ $entry->id }}, -1)" @disabled($loop->first) class="rounded-lg p-2 text-ink-secondary hover:bg-surface-raised hover:text-ink disabled:opacity-30" title="Move up"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                                            <button wire:click="moveEntry({{ $entry->id }}, 1)" @disabled($loop->last) class="rounded-lg p-2 text-ink-secondary hover:bg-surface-raised hover:text-ink disabled:opacity-30" title="Move down"><i class="fa-solid fa-arrow-down text-xs"></i></button>
                                            <button wire:click="startEditEntry({{ $entry->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface-raised hover:text-ink" title="Edit"><i class="fa-solid fa-pen text-xs"></i></button>
                                            <button wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Delete this line?" class="rounded-lg p-2 text-ink-secondary hover:bg-danger-tint hover:text-danger" title="Delete"><i class="fa-solid fa-trash text-xs"></i></button>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            @if ($addingToFloorId === $floor->id)
                                @include('livewire.admin.phone-directory.entry-form', ['key' => "add-{$floor->id}"])
                            @elseif ($floor->entries->isEmpty())
                                <tr><td colspan="4" class="px-4 py-6 text-center text-xs text-ink-tertiary">No lines on this floor yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($addingToFloorId !== $floor->id)
                    <div class="border-t border-line px-4 py-2">
                        <button wire:click="startAddEntry({{ $floor->id }})" class="text-xs font-semibold text-brand hover:underline"><i class="fa-solid fa-plus mr-1"></i>Add line</button>
                    </div>
                @endif
            </div>
        @endforeach

        <button wire:click="openCreateFloor" class="flex items-center justify-center gap-2 rounded-card border-2 border-dashed border-line px-4 py-4 text-sm font-semibold text-ink-secondary hover:border-brand hover:text-brand">
            <i class="fa-solid fa-plus"></i>Add Floor to {{ $building->name }}
        </button>
    @endif

    @if ($showForm && $formType === 'building')
        <x-admin.drawer :title="$editingId ? 'Edit Building' : 'Add Building'">
            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Building Name" :error="$errors->first('buildingForm.name')">
                    <input type="text" wire:model="buildingForm.name" placeholder="e.g. Building A" class="input">
                </x-admin.field>
                <x-admin.field label="Trunkline (optional)" :error="$errors->first('buildingForm.trunkline')">
                    <input type="text" wire:model="buildingForm.trunkline" placeholder="e.g. 8 521-8999" class="input">
                </x-admin.field>
                <x-admin.field label="Mobile (optional)" :error="$errors->first('buildingForm.mobile')">
                    <input type="text" wire:model="buildingForm.mobile" placeholder="e.g. 0999-888-9333" class="input">
                </x-admin.field>
                <x-admin.field label="Last Update" :error="$errors->first('buildingForm.last_updated_on')">
                    <input type="date" wire:model="buildingForm.last_updated_on" class="input">
                    <span class="text-[11px] text-ink-tertiary">Set automatically whenever a floor or line changes — override it here if needed.</span>
                </x-admin.field>
            </div>

            @if ($editingId)
                <div class="mt-8 rounded-lg border border-danger/30 bg-danger-tint px-4 py-3.5">
                    <p class="text-sm font-semibold text-danger">Delete this building</p>
                    <p class="mb-3 text-xs text-ink-secondary">Removes the building's tab along with every floor and line in it. This can't be undone.</p>
                    <button type="button" wire:click="deleteBuilding({{ $editingId }})" wire:confirm="Delete this building and all of its floors and lines?" class="btn-secondary text-xs text-danger"><i class="fa-solid fa-trash"></i>Delete Building</button>
                </div>
            @endif
        </x-admin.drawer>
    @endif

    @if ($showForm && $formType === 'floor')
        <x-admin.drawer :title="$editingId ? 'Edit Floor' : 'Add Floor'">
            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Floor Name" :error="$errors->first('floorForm.name')">
                    <input type="text" wire:model="floorForm.name" placeholder="e.g. 9th Floor" class="input">
                </x-admin.field>
                <x-admin.field label="Access Code (optional)" :error="$errors->first('floorForm.access_code')">
                    <input type="text" wire:model="floorForm.access_code" placeholder="Shown on the right of the floor bar" class="input">
                </x-admin.field>
            </div>

            @if ($editingId)
                <div class="mt-8 rounded-lg border border-danger/30 bg-danger-tint px-4 py-3.5">
                    <p class="text-sm font-semibold text-danger">Delete this floor</p>
                    <p class="mb-3 text-xs text-ink-secondary">Removes the floor and every line on it. This can't be undone.</p>
                    <button type="button" wire:click="deleteFloor({{ $editingId }})" wire:confirm="Delete this floor and all of its lines?" class="btn-secondary text-xs text-danger"><i class="fa-solid fa-trash"></i>Delete Floor</button>
                </div>
            @endif
        </x-admin.drawer>
    @endif
</div>
