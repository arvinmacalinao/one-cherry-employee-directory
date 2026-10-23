<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search users…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <span class="text-xs text-ink-tertiary">{{ $users->count() }} users</span>
        <button wire:click="openCreate" class="btn-primary ml-auto text-xs"><i class="fa-solid fa-plus"></i>Add User</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[640px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Last Sign-in</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3">
                            <span class="block font-semibold">{{ $user->name }} @if ($user->id === auth()->id()) <span class="text-xs font-normal text-ink-tertiary">(you)</span> @endif</span>
                            <span class="block text-xs text-ink-secondary">{{ $user->email }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $user->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-ink-secondary">No users match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        @php $editingSelf = $editingId === auth()->id(); @endphp
        <x-admin.drawer :title="$editingId ? 'Edit User' : 'Add User'">
            <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-brand-tint px-3.5 py-3 text-xs text-ink-secondary">
                <i class="fa-solid fa-circle-info mt-0.5 text-brand"></i>
                <span><strong>Admin</strong> can only edit the phone directory. <strong>Super Admin</strong> can use every module, including the employee directory and this page.</span>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Name" :error="$errors->first('form.name')">
                    <input type="text" wire:model="form.name" class="input">
                </x-admin.field>
                <x-admin.field label="Email" :error="$errors->first('form.email')">
                    <input type="email" wire:model="form.email" class="input">
                </x-admin.field>
                <x-admin.field label="Role" :error="$errors->first('form.role')">
                    <select wire:model="form.role" class="input" @disabled($editingSelf)>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                    @if ($editingSelf)
                        <span class="text-[11px] text-ink-tertiary">You can't change your own role.</span>
                    @endif
                </x-admin.field>
                <x-admin.field :label="$editingId ? 'New Password (leave blank to keep)' : 'Password'" :error="$errors->first('form.password')">
                    <input type="password" wire:model="form.password" autocomplete="new-password" class="input">
                </x-admin.field>
                @unless ($editingSelf)
                    <x-admin.field label="Active">
                        <label class="flex items-center gap-2.5">
                            <span class="switch {{ $form['is_active'] ? 'switch-on' : 'switch-off' }}" wire:click="$toggle('form.is_active')">
                                <span class="switch-dot" style="transform: translateX({{ $form['is_active'] ? '20px' : '2px' }})"></span>
                            </span>
                            <span class="text-xs text-ink-secondary">Inactive users can't sign in</span>
                        </label>
                    </x-admin.field>
                @endunless
            </div>
        </x-admin.drawer>
    @endif
</div>
