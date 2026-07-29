<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search designations…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="text-xs text-ink-tertiary">{{ $designations->count() }} designations</span>
        <button wire:click="openCreate" class="btn-primary ml-auto text-xs"><i class="fa-solid fa-plus"></i>Add Designation</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[560px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Designation</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($designations as $designation)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $designation->name }}</p>
                            <p class="text-xs text-ink-tertiary">{{ $designation->hr_ref_id ? "d_id: {$designation->hr_ref_id}" : 'Directory-managed' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $designation->employees_count }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $designation->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $designation->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $designation->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-ink-secondary">No designations match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-admin.drawer :title="$editingId ? 'Edit Designation' : 'Add Designation'">
            @if ($lockedHrRef)
                <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-brand-tint px-3.5 py-3 text-xs text-ink-secondary">
                    <i class="fa-solid fa-circle-info mt-0.5 text-brand"></i>
                    <span>Linked to HR record <code class="rounded bg-surface-raised px-1.5 py-0.5">d_id: {{ $lockedHrRef }}</code>. Name stays in sync automatically — this is the field that changes on a promotion.</span>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Designation Name" :locked="(bool) $lockedHrRef">
                    <input type="text" wire:model="form.name" @disabled($lockedHrRef) class="input">
                </x-admin.field>
                <x-admin.field label="Active">
                    <label class="flex items-center gap-2.5">
                        <span class="switch {{ $form['is_active'] ? 'switch-on' : 'switch-off' }}" wire:click="$toggle('form.is_active')">
                            <span class="switch-dot" style="transform: translateX({{ $form['is_active'] ? '20px' : '2px' }})"></span>
                        </span>
                        <span class="text-xs text-ink-secondary">Inactive designations are hidden from filters</span>
                    </label>
                </x-admin.field>
            </div>
        </x-admin.drawer>
    @endif
</div>
