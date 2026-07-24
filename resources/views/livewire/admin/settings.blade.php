<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="card p-6">
            <p class="mb-4 font-display text-sm font-bold">General</p>
            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Application Name">
                    <input type="text" wire:model="app_name" class="input">
                </x-admin.field>
                <x-admin.field label="Support Email">
                    <input type="text" wire:model="support_email" class="input">
                </x-admin.field>
                <x-admin.field label="Timezone">
                    <select wire:model="timezone" class="input">
                        <option value="Asia/Manila">Asia/Manila (GMT+8)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </x-admin.field>
            </div>
        </div>

        <div class="card p-6">
            <p class="mb-4 font-display text-sm font-bold">HR Sync Schedule</p>
            <div class="flex flex-col gap-2.5">
                @foreach ([
                    'hourly' => ['Every hour', 'Recommended when the Group hires or transfers people frequently.'],
                    'nightly' => ['Every midnight', 'Lighter load — fine if HR changes are infrequent.'],
                ] as $value => [$title, $desc])
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2.5 {{ $hr_sync_schedule === $value ? 'border-brand bg-brand-tint' : 'border-line' }}">
                        <input type="radio" wire:model="hr_sync_schedule" value="{{ $value }}" class="text-brand focus:ring-brand">
                        <span>
                            <span class="block text-sm font-semibold">{{ $title }}</span>
                            <span class="block text-xs text-ink-secondary">{{ $desc }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="card p-6">
            <p class="mb-4 font-display text-sm font-bold">Branding</p>
            <div class="mb-4 flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-dashed border-line-strong text-ink-tertiary">
                    <i class="fa-solid fa-upload"></i>
                </div>
                <p class="text-xs text-ink-secondary">Titles use <strong class="text-ink">Proxima Nova</strong>, body text uses <strong class="text-ink">Mint Sans</strong>, self-hosted from <code>public/fonts</code>.</p>
            </div>
            <p class="mb-2 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Brand Palette</p>
            <div class="flex items-center gap-2">
                @foreach (['#790002', '#77787B', '#F6F6F6', '#34C759', '#FF3B30'] as $swatch)
                    <span class="h-7 w-7 rounded-lg border border-line" style="background: {{ $swatch }}" title="{{ $swatch }}"></span>
                @endforeach
                <span class="ml-1.5 text-[11px] text-ink-tertiary">Defined in design tokens, not editable here</span>
            </div>
        </div>

        <div class="card p-6">
            <p class="mb-4 font-display text-sm font-bold">Roles</p>
            <div class="flex flex-col gap-2.5">
                @foreach ($roles as $role)
                    <div class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                        <div>
                            <p class="text-sm font-semibold">{{ $role->name }}</p>
                            <p class="text-xs text-ink-secondary">{{ $role->name === 'Employee' ? 'Search & view only — cannot edit any record' : 'Full CRUD on Employees, Companies, Departments, Designations, Offices' }}</p>
                        </div>
                        <span class="badge {{ $role->permissions->count() ? 'bg-brand-tint text-brand' : 'bg-surface text-ink-secondary' }}">{{ $role->permissions->count() }} permissions</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button wire:click="save" class="btn-primary"><i class="fa-solid fa-check"></i>Save Settings</button>
    </div>
</div>
