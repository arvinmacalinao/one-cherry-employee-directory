<div class="flex flex-col gap-6">
    @include('livewire.partials.flash-banner')
    @error('merge') <div class="rounded-control bg-danger-tint px-4 py-2.5 text-sm font-semibold text-danger">{{ $message }}</div> @enderror

    <div class="card flex flex-wrap items-center gap-4 p-5">
        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-tint text-brand"><i class="fa-solid fa-database"></i></div>
        <div class="min-w-[220px] flex-1">
            <p class="text-sm font-semibold">{{ $lastSync ? 'Last synced '.$lastSync->started_at->diffForHumans() : 'Never synced yet' }}</p>
            <p class="text-xs text-ink-secondary">Pulled from the HR REST API · keyed on <code>employee_code</code> · runs {{ config('hr_sync.schedule') }} via Laravel Scheduler</p>
        </div>
        <button wire:click="runSync" wire:loading.attr="disabled" wire:target="runSync" class="btn-primary">
            <i wire:loading.remove wire:target="runSync" class="fa-solid fa-arrows-rotate"></i>
            <i wire:loading wire:target="runSync" class="fa-solid fa-arrows-rotate animate-spin"></i>
            <span wire:loading.remove wire:target="runSync">Run Sync Now</span>
            <span wire:loading wire:target="runSync">Syncing…</span>
        </button>
    </div>

    <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_imported ?? 0 }}</p><p class="text-xs text-ink-secondary">New Hires</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_transferred ?? 0 }}</p><p class="text-xs text-ink-secondary">Promotions</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums">{{ $lastSync?->records_deactivated ?? 0 }}</p><p class="text-xs text-ink-secondary">Deactivated</p></div>
        <div class="rounded-card border border-line bg-surface-raised p-4"><p class="font-display text-xl font-bold tabular-nums {{ ($lastSync?->errors ? count($lastSync->errors) : 0) > 0 ? 'text-danger' : '' }}">{{ $lastSync?->errors ? count($lastSync->errors) : 0 }}</p><p class="text-xs text-ink-secondary">Warnings</p></div>
    </div>

    @php $totalUnmapped = $unmappedCompanies->count() + $unmappedDepartments->count() + $unmappedDesignations->count(); @endphp
    <div>
        <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-bold tracking-wide text-ink-tertiary uppercase">Unmapped records</p>
            <span class="text-xs text-ink-tertiary">Auto-created as stubs so sync never blocks</span>
        </div>

        @if ($totalUnmapped === 0)
            <div class="card flex flex-col items-center gap-2.5 px-6 py-10 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-success-tint text-success"><i class="fa-solid fa-check"></i></div>
                <p class="text-sm font-semibold">All clear</p>
                <p class="text-xs text-ink-secondary">Every HR record maps cleanly to an existing company, department, and designation.</p>
            </div>
        @else
            <div class="flex flex-col gap-2.5">
                @foreach ($unmappedCompanies as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="uc-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">Unmapped Company #{{ $stub->hr_ref_id }}</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) reference this ID with no local match</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($companyOptions->reject(fn ($c) => $c->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('company', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <a href="{{ route('admin.companies.index') }}" wire:navigate class="btn-primary text-xs">Rename</a>
                        </div>
                    </div>
                @endforeach

                @foreach ($unmappedDepartments as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="ud-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">Unmapped Department #{{ $stub->hr_ref_id }}</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) at {{ $stub->company->name }} reference this ID with no local match</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($departmentOptions->where('company_id', $stub->company_id)->reject(fn ($d) => $d->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('department', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <a href="{{ route('admin.departments.index') }}" wire:navigate class="btn-primary text-xs">Rename</a>
                        </div>
                    </div>
                @endforeach

                @foreach ($unmappedDesignations as $stub)
                    <div class="flex flex-wrap items-center gap-3.5 rounded-card bg-warning-tint p-4" wire:key="ug-{{ $stub->id }}">
                        <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-lg bg-bg text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-sm font-semibold">Unmapped Designation #{{ $stub->hr_ref_id }}</p>
                            <p class="text-xs text-ink-secondary">{{ $stub->employees_count }} employee(s) at {{ $stub->company->name }} reference this ID with no local match</p>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ target: '' }">
                            <select x-model="target" class="input max-w-[200px]">
                                <option value="">Merge into…</option>
                                @foreach ($designationOptions->where('company_id', $stub->company_id)->reject(fn ($d) => $d->id === $stub->id) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secondary text-xs" @click="target && $wire.mergeUnmapped('designation', {{ $stub->id }}, parseInt(target))">Merge</button>
                            <a href="{{ route('admin.designations.index') }}" wire:navigate class="btn-primary text-xs">Rename</a>
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
                        <th class="px-4 py-3">Deactivated</th>
                        <th class="px-4 py-3">Warnings</th>
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
                            <td class="px-4 py-3">{{ $run->records_transferred }}</td>
                            <td class="px-4 py-3">{{ $run->records_deactivated }}</td>
                            <td class="px-4 py-3 {{ ($run->errors ? count($run->errors) : 0) > 0 ? 'font-semibold text-danger' : '' }}">{{ $run->errors ? count($run->errors) : 0 }}</td>
                            <td class="px-4 py-3 text-ink-secondary">{{ $run->triggeredBy?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-ink-secondary">No sync runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $history->links() }}</div>
    </div>
</div>
