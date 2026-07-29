<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex items-center gap-2.5">
        <p class="text-xs text-ink-tertiary">Shown on Home within their publish/expiry window. Expired announcements stay listed here for history — they're never deleted, just filtered off Home.</p>
        <button wire:click="openCreate" class="btn-primary ml-auto text-xs"><i class="fa-solid fa-plus"></i>Add Announcement</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Published</th>
                    <th class="px-4 py-3">Expires</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Author</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($announcements as $announcement)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $announcement->title }}</p>
                            <p class="max-w-xs truncate text-xs text-ink-tertiary">{{ $announcement->body }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $announcement->published_at?->format('M j, Y') ?? 'Draft' }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $announcement->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                        <td class="px-4 py-3">
                            @if ($announcement->is_expired)
                                <span class="badge badge-inactive">Expired</span>
                            @elseif (! $announcement->is_active)
                                <span class="badge badge-inactive">Disabled</span>
                            @elseif (! $announcement->published_at || $announcement->published_at->isFuture())
                                <span class="badge badge-leave">Scheduled</span>
                            @else
                                <span class="badge badge-active">Live</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $announcement->author?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $announcement->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-pen"></i></button>
                            <button wire:click="delete({{ $announcement->id }})" wire:confirm="Delete this announcement permanently?" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-danger"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-secondary">No announcements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $announcements->links() }}</div>

    @if ($showForm)
        <x-admin.drawer :title="$editingId ? 'Edit Announcement' : 'Add Announcement'">
            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Title" :error="$errors->first('form.title')">
                    <input type="text" wire:model="form.title" class="input">
                </x-admin.field>
                <x-admin.field label="Body" :error="$errors->first('form.body')">
                    <textarea wire:model="form.body" rows="4" class="input"></textarea>
                </x-admin.field>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Publish At">
                        <input type="datetime-local" wire:model="form.published_at" class="input">
                    </x-admin.field>
                    <x-admin.field label="Expires At" :error="$errors->first('form.expires_at')">
                        <input type="datetime-local" wire:model="form.expires_at" class="input">
                    </x-admin.field>
                </div>
                <x-admin.field label="Active">
                    <label class="flex items-center gap-2.5">
                        <span class="switch {{ $form['is_active'] ? 'switch-on' : 'switch-off' }}" wire:click="$toggle('form.is_active')">
                            <span class="switch-dot" style="transform: translateX({{ $form['is_active'] ? '20px' : '2px' }})"></span>
                        </span>
                        <span class="text-xs text-ink-secondary">Turn off to hide without deleting</span>
                    </label>
                </x-admin.field>
            </div>
        </x-admin.drawer>
    @endif
</div>
