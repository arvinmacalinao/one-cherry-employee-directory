{{-- Inline add/edit row for one phone line. Expects: $key. --}}
<tr wire:key="{{ $key }}" class="border-b border-line bg-brand-tint/40 last:border-b-0">
    <td class="px-2 py-2 align-top">
        <input type="text" wire:model="entryForm.department" wire:keydown.enter="saveEntry" wire:keydown.escape="cancelEntry" list="phone-departments" placeholder="Company / department" class="input" autofocus>
        @error('entryForm.department') <span class="mt-1 block text-[11px] font-medium text-danger">{{ $message }}</span> @enderror
    </td>
    <td class="px-2 py-2 align-top">
        <input type="text" wire:model="entryForm.name" wire:keydown.enter="saveEntry" wire:keydown.escape="cancelEntry" placeholder="Name(s), e.g. Joyce / Erick" class="input">
        @error('entryForm.name') <span class="mt-1 block text-[11px] font-medium text-danger">{{ $message }}</span> @enderror
    </td>
    <td class="px-2 py-2 align-top">
        <input type="text" wire:model="entryForm.local" wire:keydown.enter="saveEntry" wire:keydown.escape="cancelEntry" placeholder="e.g. 1820" class="input">
        @error('entryForm.local') <span class="mt-1 block text-[11px] font-medium text-danger">{{ $message }}</span> @enderror
    </td>
    <td class="px-2 py-2 text-right align-top whitespace-nowrap">
        <button type="button" wire:click="saveEntry" class="btn-primary px-3 text-xs"><i class="fa-solid fa-check"></i>Save</button>
        <button type="button" wire:click="cancelEntry" class="btn-ghost px-3 text-xs">Cancel</button>
    </td>
</tr>
