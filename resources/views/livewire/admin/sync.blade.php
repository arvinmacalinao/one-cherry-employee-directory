<div class="flex flex-col gap-6">
    @include('livewire.partials.flash-banner')
    @error('merge') <div class="rounded-control bg-danger-tint px-4 py-2.5 text-sm font-semibold text-danger">{{ $message }}</div> @enderror

    @unless ($firstSyncCompleted)
        <div class="card flex flex-wrap items-center gap-4 bg-warning-tint p-5">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="min-w-[260px] flex-1">
                <p class="text-sm font-semibold">First live sync not yet run</p>
                <p class="text-xs text-ink-secondary">The scheduler stays off, and Sync Now is disabled, until you review a preview and confirm the first run. This is a one-time gate — see architecture-plan.md §2.5.</p>
            </div>
            <button wire:click="generatePreview" wire:loading.attr="disabled" wire:target="generatePreview" class="btn-primary">
                <i wire:loading.remove wire:target="generatePreview" class="fa-solid fa-magnifying-glass"></i>
                <i wire:loading wire:target="generatePreview" class="fa-solid fa-arrows-rotate animate-spin"></i>
                <span wire:loading.remove wire:target="generatePreview">Generate Preview</span>
                <span wire:loading wire:target="generatePreview">Fetching…</span>
            </button>
        </div>
    @endunless

    @if ($preview)
        <div class="card flex flex-col gap-5 p-5">
            <div class="flex items-center justify-between">
                <p class="font-display text-sm font-bold">Sync Preview — nothing has been written yet</p>
                @unless ($firstSyncCompleted)
                    <button wire:click="confirmFirstSync" wire:loading.attr="disabled" wire:target="confirmFirstSync" class="btn-primary text-xs">
                        <i class="fa-solid fa-check"></i>Confirm &amp; Run First Sync
                    </button>
                @endunless
            </div>

            <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
                <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ count($preview->newEmployees) }}</p><p class="text-xs text-ink-secondary">New Employees</p></div>
                <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ count($preview->updatedEmployees) }}</p><p class="text-xs text-ink-secondary">Updated Employees</p></div>
                <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums">{{ count($preview->becomingInactive) }}</p><p class="text-xs text-ink-secondary">Would Become Inactive</p></div>
                <div class="rounded-lg border border-line p-3.5"><p class="font-display text-lg font-bold tabular-nums {{ count($preview->warnings) > 0 ? 'text-danger' : '' }}">{{ count($preview->warnings) }}</p><p class="text-xs text-ink-secondary">Warnings</p></div>
            </div>

            @foreach ([
                ['label' => 'New employees', 'rows' => $preview->newEmployees, 'type' => 'new'],
                ['label' => 'Department changes', 'rows' => $preview->departmentChanges, 'type' => 'change'],
                ['label' => 'Designation changes', 'rows' => $preview->designationChanges, 'type' => 'change'],
                ['label' => 'Supervisor changes', 'rows' => $preview->supervisorChanges, 'type' => 'change'],
                ['label' => 'Employment status changes', 'rows' => $preview->statusChanges, 'type' => 'change'],
                ['label' => 'Would become inactive', 'rows' => $preview->becomingInactive, 'type' => 'inactive'],
            ] as $section)
                @if (count($section['rows']) > 0)
                    <div>
                        <p class="mb-2 text-xs font-bold tracking-wide text-ink-tertiary uppercase">{{ $section['label'] }} ({{ count($section['rows']) }})</p>
                        <div class="table-wrap overflow-x-auto rounded-card border border-line">
                            <table class="w-full min-w-[480px] border-collapse text-sm">
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr class="border-b border-line last:border-b-0">
                                            <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                                            <td class="px-3 py-2 text-xs text-ink-tertiary">{{ $row['employee_id'] }}</td>
                                            @if ($section['type'] === 'change')
                                                <td class="px-3 py-2 text-xs text-ink-secondary">{{ $row['from'] ?? '—' }} → {{ $row['to'] ?? '—' }}</td>
                                            @elseif ($section['type'] === 'new')
                                                <td class="px-3 py-2 text-xs text-ink-secondary">{{ $row['company'] }} · {{ $row['department'] }} · {{ $row['designation'] }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach

            @if (count($preview->warnings) > 0)
                <div>
                    <p class="mb-2 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Warnings</p>
                    <ul class="flex flex-col gap-1 text-xs text-danger">
                        @foreach ($preview->warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($preview->isEmpty())
                <p class="py-4 text-center text-sm text-ink-secondary">No changes — OCED already matches the HR feed.</p>
            @endif
        </div>
    @endif

    <div class="card flex flex-wrap items-center gap-4 p-5">
        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid fa-database"></i></div>
        <div class="min-w-[220px] flex-1">
            <p class="text-sm font-semibold">{{ $lastSync ? 'Last synced '.$lastSync->started_at->diffForHumans() : 'Never synced yet' }}</p>
            <p class="text-xs text-ink-secondary">Pulled from the HR REST API · keyed on <code>employee_id</code> · runs {{ config('hr_sync.schedule') }} via Laravel Scheduler</p>
        </div>
        @if ($firstSyncCompleted)
            <button wire:click="generatePreview" wire:loading.attr="disabled" wire:target="generatePreview,runSync" class="btn-secondary">Preview Sync</button>
            <button wire:click="runSync" wire:loading.attr="disabled" wire:target="runSync,generatePreview" class="btn-primary">
                <i wire:loading.remove wire:target="runSync" class="fa-solid fa-arrows-rotate"></i>
                <i wire:loading wire:target="runSync" class="fa-solid fa-arrows-rotate animate-spin"></i>
                <span wire:loading.remove wire:target="runSync">Sync Now</span>
                <span wire:loading wire:target="runSync">Syncing…</span>
            </button>
        @else
            <span class="text-xs font-semibold text-ink-tertiary">Sync Now is disabled until the first sync is reviewed and confirmed above.</span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_imported ?? 0 }}</p><p class="text-xs text-ink-secondary">New Hires</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_promoted ?? 0 }}</p><p class="text-xs text-ink-secondary">Promotions</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_deactivated ?? 0 }}</p><p class="text-xs text-ink-secondary">Deactivated</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums {{ ($lastSync?->errors ? count($lastSync->errors) : 0) > 0 ? 'text-danger' : '' }}">{{ $lastSync?->errors ? count($lastSync->errors) : 0 }}</p><p class="text-xs text-ink-secondary">Errors</p></div>
    </div>

    @php $totalNeedsReview = $needsReviewCompanies->count() + $needsReviewDepartments->count() + $needsReviewDesignations->count(); @endphp
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-bold tracking-wide text-ink-tertiary uppercase">Needs review</p>
            <div class="flex items-center gap-2">
                <span class="text-xs text-ink-tertiary">Auto-created from a new HR company/department/designation so sync never blocks</span>
                @if ($totalNeedsReview > 0)
                    <button
                        type="button"
                        wire:click="markAllReviewed"
                        wire:confirm="Mark all {{ $totalNeedsReview }} flagged records as reviewed? This only clears the flag — it doesn't merge anything, so use it once you're confident none of these are actually duplicates."
                        class="btn-secondary text-xs"
                    >
                        <i class="fa-solid fa-check-double"></i>Mark All Reviewed ({{ $totalNeedsReview }})
                    </button>
                @endif
            </div>
        </div>

        @if ($totalNeedsReview === 0)
            <div class="card flex flex-col items-center gap-2.5 px-6 py-10 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-success-tint text-success"><i class="fa-solid fa-check"></i></div>
                <p class="text-sm font-semibold">All clear</p>
                <p class="text-xs text-ink-secondary">Every company, department, and designation HR sends has already been reviewed.</p>
            </div>
        @else
            <div class="flex flex-col gap-2.5">
                @if ($needsReviewCompanies->isNotEmpty())
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold tracking-wide text-ink-tertiary uppercase">Companies ({{ $needsReviewCompanies->count() }})</p>
                        <button type="button" wire:click="markAllReviewed('company')" wire:confirm="Mark all {{ $needsReviewCompanies->count() }} companies as reviewed?" class="text-xs font-semibold text-brand hover:underline">Mark all reviewed</button>
                    </div>
                @endif
                @foreach ($needsReviewCompanies as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="uc-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">New Company: "{{ $stub->name }}"</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) · HR sent this identity for the first time — add logo, address, etc.</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($companyOptions->reject(fn ($c) => $c->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('company', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <button type="button" wire:click="markReviewed('company', {{ $stub->id }})" class="btn-secondary text-xs">Mark Reviewed</button>
                            <a href="{{ route('admin.companies.index') }}" wire:navigate class="btn-primary text-xs">Edit</a>
                        </div>
                    </div>
                @endforeach

                @if ($needsReviewDepartments->isNotEmpty())
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-[11px] font-bold tracking-wide text-ink-tertiary uppercase">Departments ({{ $needsReviewDepartments->count() }})</p>
                        <button type="button" wire:click="markAllReviewed('department')" wire:confirm="Mark all {{ $needsReviewDepartments->count() }} departments as reviewed?" class="text-xs font-semibold text-brand hover:underline">Mark all reviewed</button>
                    </div>
                @endif
                @foreach ($needsReviewDepartments as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="ud-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">New Department: "{{ $stub->name }}"</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) · HR sent this identity for the first time</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($departmentOptions->reject(fn ($d) => $d->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('department', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <button type="button" wire:click="markReviewed('department', {{ $stub->id }})" class="btn-secondary text-xs">Mark Reviewed</button>
                            <a href="{{ route('admin.departments.index') }}" wire:navigate class="btn-primary text-xs">Edit</a>
                        </div>
                    </div>
                @endforeach

                @if ($needsReviewDesignations->isNotEmpty())
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-[11px] font-bold tracking-wide text-ink-tertiary uppercase">Designations ({{ $needsReviewDesignations->count() }})</p>
                        <button type="button" wire:click="markAllReviewed('designation')" wire:confirm="Mark all {{ $needsReviewDesignations->count() }} designations as reviewed?" class="text-xs font-semibold text-brand hover:underline">Mark all reviewed</button>
                    </div>
                @endif
                @foreach ($needsReviewDesignations as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="ug-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">New Designation: "{{ $stub->name }}"</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) · HR sent this identity for the first time</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($designationOptions->reject(fn ($d) => $d->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('designation', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <button type="button" wire:click="markReviewed('designation', {{ $stub->id }})" class="btn-secondary text-xs">Mark Reviewed</button>
                            <a href="{{ route('admin.designations.index') }}" wire:navigate class="btn-primary text-xs">Edit</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <p class="mb-3 text-xs font-bold tracking-wide text-ink-tertiary uppercase">Sync history</p>
        <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
            <table class="w-full min-w-[760px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                        <th class="px-4 py-3">Date &amp; Time</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">New</th>
                        <th class="px-4 py-3">Promotions</th>
                        <th class="px-4 py-3">Status Changes</th>
                        <th class="px-4 py-3">Deactivated</th>
                        <th class="px-4 py-3">Errors</th>
                        <th class="px-4 py-3">Triggered By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $run)
                        <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                            <td class="px-4 py-3 text-ink-secondary">{{ $run->started_at->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $run->sync_type->value }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $run->status?->value === 'success' ? 'badge-active' : ($run->status?->value === 'partial' ? 'badge-leave' : 'badge-inactive') }}">{{ ucfirst($run->status?->value ?? 'running') }}</span></td>
                            <td class="px-4 py-3">{{ $run->records_imported }}</td>
                            <td class="px-4 py-3">{{ $run->records_promoted }}</td>
                            <td class="px-4 py-3">{{ $run->records_status_changed }}</td>
                            <td class="px-4 py-3">{{ $run->records_deactivated }}</td>
                            <td class="px-4 py-3 {{ ($run->errors ? count($run->errors) : 0) > 0 ? 'font-semibold text-danger' : '' }}">{{ $run->errors ? count($run->errors) : 0 }}</td>
                            <td class="px-4 py-3 text-ink-secondary">{{ $run->triggeredBy?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-ink-secondary">No sync runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $history->links() }}</div>
    </div>
</div>
