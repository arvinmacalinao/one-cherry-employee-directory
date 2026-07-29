<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search companies…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="text-xs text-ink-tertiary">{{ $companies->count() }} companies</span>
        <button wire:click="openCreate" class="btn-primary ml-auto text-xs"><i class="fa-solid fa-plus"></i>Add Company</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">HR Ref</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                @if ($company->getFirstMediaUrl('logo', 'thumb'))
                                    <img src="{{ $company->getFirstMediaUrl('logo', 'thumb') }}" alt="{{ $company->name }}" class="h-8.5 w-8.5 flex-shrink-0 rounded-lg object-cover">
                                @else
                                    <span class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-brand-tint text-xs font-bold text-brand">
                                        {{ collect(explode(' ', $company->name))->map(fn ($w) => $w[0])->take(3)->implode('') }}
                                    </span>
                                @endif
                                <span class="font-semibold">{{ $company->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $company->hr_ref_id ? "c_id: {$company->hr_ref_id}" : '—' }}</td>
                        <td class="px-4 py-3">{{ $company->employees_count }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $company->address ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $company->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $company->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $company->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-secondary">No companies match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-admin.drawer :title="$editingId ? 'Edit Company' : 'Add Company'">
            @if ($lockedHrRef)
                <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-brand-tint px-3.5 py-3 text-xs text-ink-secondary">
                    <i class="fa-solid fa-circle-info mt-0.5 text-brand"></i>
                    <span>Linked to HR record <code class="rounded bg-surface-raised px-1.5 py-0.5">c_id: {{ $lockedHrRef }}</code>. The Name field stays in sync automatically; everything else here is managed in the Directory only.</span>
                </div>
            @endif

            <div class="mb-4 flex items-center gap-4 border-b border-line pb-5">
                <div class="h-16 w-16 flex-shrink-0">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 rounded-lg border border-line object-cover" alt="Logo preview">
                    @elseif ($editingCompany?->getFirstMediaUrl('logo', 'thumb'))
                        <img src="{{ $editingCompany->getFirstMediaUrl('logo', 'thumb') }}" class="h-16 w-16 rounded-lg border border-line object-cover" alt="Current logo">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-brand-tint font-display text-lg font-bold text-brand">
                            {{ collect(explode(' ', $form['name'] ?: 'C'))->map(fn ($w) => $w[0] ?? '')->take(3)->implode('') }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-col items-start gap-1.5">
                    <label class="btn-secondary w-fit cursor-pointer !py-1.5 text-xs">
                        <i class="fa-solid fa-image"></i>{{ $editingCompany?->hasMedia('logo') || $logo ? 'Change Logo' : 'Upload Logo' }}
                        <input type="file" wire:model="logo" accept="image/*" class="hidden">
                    </label>
                    @error('logo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-ink-tertiary">JPG or PNG, up to 2MB.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.field label="Company Name" :locked="(bool) $lockedHrRef" :full="true">
                    <input type="text" wire:model="form.name" @disabled($lockedHrRef) class="input">
                </x-admin.field>
                <x-admin.field label="Address" :full="true">
                    <input type="text" wire:model="form.address" class="input">
                </x-admin.field>
                <x-admin.field label="Phone">
                    <input type="text" wire:model="form.phone" class="input">
                </x-admin.field>
                <x-admin.field label="Email">
                    <input type="text" wire:model="form.email" class="input">
                </x-admin.field>
                <x-admin.field label="Website" :full="true">
                    <input type="text" wire:model="form.website" class="input">
                </x-admin.field>
                <x-admin.field label="Active" :full="true">
                    <label class="flex items-center gap-2.5">
                        <span class="switch {{ $form['is_active'] ? 'switch-on' : 'switch-off' }}" wire:click="$toggle('form.is_active')">
                            <span class="switch-dot" style="transform: translateX({{ $form['is_active'] ? '20px' : '2px' }})"></span>
                        </span>
                        <span class="text-xs text-ink-secondary">Inactive companies are hidden from the directory</span>
                    </label>
                </x-admin.field>
            </div>
        </x-admin.drawer>
    @endif
</div>
