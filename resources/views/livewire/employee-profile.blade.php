@php
    // Presentational only — color-codes whatever label HR sent. Never affects
    // directory visibility, which is governed solely by employees.is_active.
    // See architecture-plan.md §2.5, §3.2.
    $statusLabel = $employee->status?->name ?? 'Unknown';
    $statusClass = match (true) {
        str_contains(strtolower($statusLabel), 'leave') => 'badge-leave',
        str_contains(strtolower($statusLabel), 'resign'),
        str_contains(strtolower($statusLabel), 'terminat'),
        str_contains(strtolower($statusLabel), 'awol') => 'badge-inactive',
        default => 'badge-active',
    };
@endphp

<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-3 px-1">
        <x-avatar :employee="$employee" size="h-32 w-32" textSize="text-4xl" conversion="profile" class="border-4 border-bg" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-1 flex-col gap-1">
                <h2 class="font-display text-xl">{{ $employee->full_name }}</h2>
                <p class="text-sm font-semibold text-brand">{{ $employee->designation?->name }}</p>
                <p class="text-sm text-ink-secondary">{{ $employee->department?->name }} · {{ $employee->company?->name }}</p>
                <span class="badge {{ $statusClass }} w-fit">{{ $statusLabel }}</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($employee->email)
                    <a href="mailto:{{ $employee->email }}" class="btn-secondary"><i class="fa-solid fa-envelope"></i>Email</a>
                @endif
                @if ($employee->profile?->telephone)
                    <a href="tel:{{ $employee->profile->telephone }}{{ $employee->profile->local_extension ? ',' . $employee->profile->local_extension : '' }}" class="btn-secondary"><i class="fa-solid fa-phone"></i>Call</a>
                @endif
                <!-- no need viber -->
                <!-- @if ($employee->profile?->viber_number)
                    <a href="viber://chat?number={{ $employee->profile->viber_number }}" class="btn-primary"><i class="fa-brands fa-viber"></i>Viber</a>
                @endif -->
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="card p-5">
            <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Organization</h4>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-xs text-ink-tertiary">Company</dt><dd class="font-medium">{{ $employee->company?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Department</dt><dd class="font-medium">{{ $employee->department?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Designation</dt><dd class="font-medium">{{ $employee->designation?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Corporate Email</dt><dd class="font-medium">{{ $employee->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Office Location</dt><dd class="font-medium">{{ $employee->profile?->officeLocation?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-tertiary">Contact Number</dt><dd class="font-medium">{{ $employee->profile?->viber_number ?: '—' }}</dd></div>
                <div>
                    <dt class="text-xs text-ink-tertiary">Telephone</dt>
                    <dd class="font-medium">
                        {{ $employee->profile?->telephone ?: '—' }}{{ $employee->profile?->local_extension ? ' ext. '.$employee->profile->local_extension : '' }}
                    </dd>
                </div>
                <div><dt class="text-xs text-ink-tertiary">Birthday</dt><dd class="font-medium">{{ $employee->profile?->birthday?->format('F j') ?: '—' }}</dd></div>
            </dl>
        </div>

        @if ($employee->profile?->about_me)
            <div class="card p-5">
                <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">About</h4>
                <p class="max-w-[62ch] text-sm leading-relaxed text-ink-secondary">{{ $employee->profile->about_me }}</p>
            </div>
        @endif
    </div>
</div>
