<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search office locations…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="text-xs text-ink-tertiary">{{ $offices->count() }} offices</span>
        <button wire:click="openCreate" class="btn-primary ml-auto text-xs"><i class="fa-solid fa-plus"></i>Add Office Location</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Office</th>
                    <th class="px-4 py-3">City / Country</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($offices as $office)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3 font-semibold">{{ $office->name }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $office->city }}{{ $office->country ? ', '.$office->country : '' }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $office->company?->name ?? 'Shared' }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $office->phone ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $office->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $office->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $office->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-secondary">No office locations match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-admin.drawer :title="$editingId ? 'Edit Office Location' : 'Add Office Location'">
            <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-brand-tint px-3.5 py-3 text-xs text-ink-secondary">
                <i class="fa-solid fa-circle-info mt-0.5 text-brand"></i>
                <span>Office Locations are fully Directory-managed — HR does not track structured site data, so nothing here is ever overwritten by sync.</span>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Office Name">
                    <input type="text" wire:model="form.name" class="input">
                </x-admin.field>
                <x-admin.field label="Address">
                    <input type="text" wire:model="form.address" placeholder="Street address" class="input">
                </x-admin.field>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="City">
                        <input type="text" wire:model="form.city" class="input">
                    </x-admin.field>
                    <x-admin.field label="Country">
                        <input type="text" wire:model="form.country" class="input">
                    </x-admin.field>
                </div>
                <x-admin.field label="Phone">
                    <input type="text" wire:model="form.phone" class="input">
                </x-admin.field>
                <x-admin.field label="Company (optional — leave blank if shared)">
                    <select wire:model="form.company_id" class="input">
                        <option value="">— Shared across companies —</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.field label="Active">
                    <label class="flex items-center gap-2.5">
                        <span class="switch {{ $form['is_active'] ? 'switch-on' : 'switch-off' }}" wire:click="$toggle('form.is_active')">
                            <span class="switch-dot" style="transform: translateX({{ $form['is_active'] ? '20px' : '2px' }})"></span>
                        </span>
                        <span class="text-xs text-ink-secondary">Inactive offices are hidden from filters</span>
                    </label>
                </x-admin.field>
            </div>
        </x-admin.drawer>
    @endif
</div>
