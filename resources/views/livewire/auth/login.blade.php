<div class="w-full max-w-sm">
    <div class="mb-8 flex flex-col items-center gap-3 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand font-display text-lg font-bold text-on-brand">
            OC
        </div>
        <div>
            <p class="font-display text-base font-bold">One Cherry</p>
            <p class="text-sm text-ink-secondary">Employee Directory</p>
        </div>
    </div>

    <form wire:submit="authenticate" class="card flex flex-col gap-4 p-6">
        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-xs font-semibold text-ink-secondary">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autofocus
                class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm text-ink focus:border-brand focus:ring-4 focus:ring-brand-tint focus:outline-none"
            >
            @error('email') <span class="text-xs font-medium text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-xs font-semibold text-ink-secondary">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm text-ink focus:border-brand focus:ring-4 focus:ring-brand-tint focus:outline-none"
            >
            @error('password') <span class="text-xs font-medium text-danger">{{ $message }}</span> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-secondary">
            <input wire:model="remember" type="checkbox" class="rounded border-line text-brand focus:ring-brand">
            Keep me signed in
        </label>

        <button type="submit" class="btn-primary justify-center" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>
</div>
