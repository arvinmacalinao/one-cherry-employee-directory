@props(['title' => '', 'saveLabel' => 'Save Changes'])
<div class="fixed inset-0 z-100 flex justify-end">
    <div class="absolute inset-0 bg-black/45" wire:click="closeForm"></div>
    <div class="relative flex h-full w-full max-w-140 flex-col bg-bg shadow-popover">
        <div class="flex items-center gap-3 border-b border-line px-6 py-5">
            <h3 class="flex-1 font-display text-base">{{ $title }}</h3>
            <button type="button" wire:click="closeForm" class="rounded-lg p-1.5 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>
        <div class="flex justify-end gap-2.5 border-t border-line px-6 py-4">
            <button type="button" wire:click="closeForm" class="btn-secondary">Cancel</button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
                <i class="fa-solid fa-check"></i>{{ $saveLabel }}
            </button>
        </div>
    </div>
</div>
