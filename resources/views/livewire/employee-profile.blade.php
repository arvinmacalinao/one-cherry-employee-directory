<div class="flex flex-col gap-5">
    <div class="h-32 rounded-panel" style="background: linear-gradient(135deg, {{ $employee->company->color_theme ?? '#790002' }}, #1c1c1e)"></div>

    <div class="-mt-14 flex flex-wrap items-end gap-4 px-1">
        <span class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-full border-4 border-bg bg-brand-tint font-display text-2xl font-bold text-brand">
            {{ collect(explode(' ', $employee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
        </span>
        <div class="flex flex-1 flex-col gap-1 pb-1.5">
            <h2 class="font-display text-xl">{{ $employee->full_name }}{{ $employee->profile?->nickname ? ' "'.$employee->profile->nickname.'"' : '' }}</h2>
            <p class="text-sm font-semibold text-brand">{{ $employee->designation->name }}</p>
            <p class="text-sm text-ink-secondary">{{ $employee->department->name }} · {{ $employee->company->name }}</p>
            <span class="badge badge-{{ $employee->employment_status->value === 'on_leave' ? 'leave' : $employee->employment_status->value }} w-fit">
                {{ $employee->employment_status->label() }}
            </span>
        </div>
        <div class="flex flex-wrap gap-2 pb-1.5">
            @if ($employee->profile?->mobile_number)
                <a href="tel:{{ $employee->profile->mobile_number }}" class="btn-secondary"><i class="fa-solid fa-phone"></i>Call</a>
            @endif
            <a href="mailto:{{ $employee->email }}" class="btn-secondary"><i class="fa-solid fa-envelope"></i>Email</a>
            @if ($employee->profile?->viber_number)
                <a href="viber://chat?number={{ $employee->profile->viber_number }}" class="btn-secondary"><i class="fa-brands fa-viber"></i>Viber</a>
            @endif
            <button wire:click="toggleFavorite" class="btn-secondary">
                <i class="fa-{{ $isFavorited ? 'solid' : 'regular' }} fa-star {{ $isFavorited ? 'text-amber-400' : '' }}"></i>
                {{ $isFavorited ? 'Favorited' : 'Favorite' }}
            </button>
            <button wire:click="downloadVCard" class="btn-primary"><i class="fa-solid fa-download"></i>Download vCard</button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.6fr_1fr]">
        <div class="flex flex-col gap-4">
            <div class="card p-5">
                <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Personal Information</h4>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs text-ink-tertiary">First Name</dt><dd class="font-medium">{{ $employee->first_name }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Middle Name</dt><dd class="font-medium">{{ $employee->middle_name ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Last Name</dt><dd class="font-medium">{{ $employee->last_name }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Suffix</dt><dd class="font-medium">{{ $employee->profile?->suffix ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Gender</dt><dd class="font-medium">{{ $employee->profile?->gender ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Birthday</dt><dd class="font-medium">{{ $employee->profile?->birthday?->format('M j') ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="card p-5">
                <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Contact Information</h4>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div class="col-span-2"><dt class="text-xs text-ink-tertiary">Corporate Email</dt><dd class="font-medium">{{ $employee->email }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Personal Email</dt><dd class="font-medium">{{ $employee->profile?->personal_email ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Mobile</dt><dd class="font-medium">{{ $employee->profile?->mobile_number ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Viber</dt><dd class="font-medium">{{ $employee->profile?->viber_number ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Local Extension</dt><dd class="font-medium">{{ $employee->profile?->local_extension ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Office</dt><dd class="font-medium">{{ $employee->profile?->officeLocation?->name ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="card p-5">
                <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Organization Information</h4>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs text-ink-tertiary">Company</dt><dd class="font-medium">{{ $employee->company->name }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Department</dt><dd class="font-medium">{{ $employee->department->name }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Designation</dt><dd class="font-medium">{{ $employee->designation->name }}</dd></div>
                    <div><dt class="text-xs text-ink-tertiary">Date Hired</dt><dd class="font-medium">{{ $employee->date_hired?->format('M j, Y') ?: '—' }}</dd></div>
                </dl>
            </div>

            @if ($employee->skills->isNotEmpty())
                <div class="card p-5">
                    <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Skills</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($employee->skills as $skill)
                            <span class="rounded-full border border-line bg-surface px-3 py-1 text-xs font-medium text-ink-secondary">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($employee->profile?->about_me)
                <div class="card p-5">
                    <h4 class="mb-4 text-xs font-bold tracking-wide text-ink-tertiary uppercase">About</h4>
                    <p class="max-w-[62ch] text-sm leading-relaxed text-ink-secondary">{{ $employee->profile->about_me }}</p>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            <div class="card flex flex-col items-center gap-3 p-5 text-center">
                <h4 class="self-start text-xs font-bold tracking-wide text-ink-tertiary uppercase">Digital Business Card</h4>
                <div class="rounded-lg bg-white p-2.5">{!! $qrCodeSvg !!}</div>
                <p class="text-xs text-ink-tertiary">Scan to save contact instantly</p>
            </div>

            @if ($employee->supervisor)
                <div class="card p-5">
                    <h4 class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Reporting Manager</h4>
                    <a href="{{ route('directory.show', $employee->supervisor) }}" wire:navigate class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-surface">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-xs font-bold text-brand">
                            {{ collect(explode(' ', $employee->supervisor->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $employee->supervisor->full_name }}</p>
                            <p class="truncate text-xs text-ink-secondary">{{ $employee->supervisor->designation->name }}</p>
                        </div>
                    </a>
                </div>
            @endif

            @if ($employee->reports->isNotEmpty())
                <div class="card p-5">
                    <h4 class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Team Members</h4>
                    <div class="flex flex-col gap-1">
                        @foreach ($employee->reports as $report)
                            <a href="{{ route('directory.show', $report) }}" wire:navigate class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-surface">
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-xs font-bold text-brand">
                                    {{ collect(explode(' ', $report->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $report->full_name }}</p>
                                    <p class="truncate text-xs text-ink-secondary">{{ $report->designation->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
