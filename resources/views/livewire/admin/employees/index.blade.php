<div class="flex flex-col gap-5">
    @include('livewire.partials.flash-banner')

    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex min-w-[220px] max-w-sm flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
            <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search employees…" class="w-full border-0 bg-transparent text-sm outline-none">
        </div>
        <select wire:model.live="companyFilter" class="input max-w-[200px]">
            <option value="">All Companies</option>
            @foreach ($companyOptions as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="input max-w-[180px]">
            <option value="">All Statuses</option>
            @foreach ($statusOptions as $status)
                <option value="{{ $status->id }}">{{ $status->name }}</option>
            @endforeach
        </select>
        <span class="text-xs text-ink-tertiary">{{ $employees->total() }} employees</span>
        <button wire:click="exportCsv" class="btn-secondary ml-auto text-xs"><i class="fa-solid fa-file-export"></i>Export CSV</button>
        <button wire:click="openImportModal" class="btn-primary text-xs"><i class="fa-solid fa-file-import"></i>Import CSV</button>
    </div>

    <p class="text-xs text-ink-tertiary">Every employee originates from HR sync — there is no manual "Add Employee" here. Only email (when HR hasn't set one), birthday, Viber, telephone, office location, and about-me are editable; everything else is HR-owned and read-only. Export CSV, fill in the blanks in Excel, then Import CSV the same file back — the fastest way to bulk-add emails and extensions.</p>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[860px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Designation</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr class="border-b border-line last:border-b-0 hover:bg-surface">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :employee="$employee" size="h-8.5 w-8.5" textSize="text-xs" conversion="thumb" />
                                <div>
                                    <p class="font-semibold">{{ $employee->full_name }}</p>
                                    <p class="text-xs text-ink-tertiary">{{ $employee->employee_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->company->name ?? 'N/A'}}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->department->name ?? 'N/A'}}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->designation->name ?? 'N/A'}}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $employee->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $employee->status->name ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('directory.show', $employee) }}" wire:navigate class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink" title="View profile"><i class="fa-solid fa-eye"></i></a>
                            <button wire:click="openEdit({{ $employee->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink" title="Edit directory fields"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-secondary">No employees match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $employees->links() }}</div>

    @if ($showForm)
        <x-admin.drawer :title="$editingEmployee->full_name">
            <div class="mb-5 flex items-center gap-4 border-b border-line pb-5">
                <div class="h-20 w-20 flex-shrink-0">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 rounded-full border border-line object-cover" alt="Photo preview">
                    @elseif ($editingEmployee?->getFirstMediaUrl('photo', 'thumb'))
                        <img src="{{ $editingEmployee->getFirstMediaUrl('photo', 'thumb') }}" class="h-20 w-20 rounded-full border border-line object-cover" alt="Current photo">
                    @else
                        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-tint font-display text-xl font-bold text-brand">
                            {{ collect(explode(' ', $editingEmployee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-col items-start gap-1.5">
                    <label class="btn-secondary w-fit cursor-pointer !py-1.5 text-xs">
                        <i class="fa-solid fa-camera"></i>{{ $editingEmployee?->hasMedia('photo') || $photo ? 'Change Photo' : 'Upload Photo' }}
                        <input type="file" wire:model="photo" accept="image/*" class="hidden">
                    </label>
                    @if ($editingEmployee?->hasMedia('photo'))
                        <button type="button" wire:click="removePhoto" class="text-xs text-ink-tertiary hover:text-red-600 hover:underline">Remove photo</button>
                    @endif
                    <div wire:loading wire:target="photo" class="text-xs text-ink-tertiary">Uploading…</div>
                    @error('photo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-ink-tertiary">JPG or PNG, up to 5MB.</p>
                </div>
            </div>

            <div class="mb-5 grid grid-cols-1 gap-4 border-b border-line pb-5 sm:grid-cols-2">
                <x-admin.field label="Employee ID" :locked="true">
                    <input type="text" value="{{ $editingEmployee->employee_id }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Company" :locked="true">
                    <input type="text" value="{{ $editingEmployee->company?->name }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Department" :locked="true">
                    <input type="text" value="{{ $editingEmployee->department?->name }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Designation" :locked="true">
                    <input type="text" value="{{ $editingEmployee->designation?->name }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Employment Status" :locked="true">
                    <input type="text" value="{{ $editingEmployee->status?->name }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Supervisor" :locked="true">
                    <input type="text" value="{{ $editingEmployee->supervisor?->full_name ?? '—' }}" disabled class="input">
                </x-admin.field>
                <x-admin.field label="Date Hired" :locked="true">
                    <input type="text" value="{{ $editingEmployee->date_hired?->format('M j, Y') ?? '—' }}" disabled class="input">
                </x-admin.field>
                <p class="text-[11px] text-ink-tertiary sm:col-span-2">All fields above are HR-owned — sourced from the HR system and overwritten on every sync. See architecture-plan.md §2.5.</p>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-admin.field label="Corporate Email" :error="$errors->first('form.email')">
                    <input type="text" wire:model="form.email" class="input">
                    <p class="text-[11px] text-ink-tertiary">HR often doesn't send an email for this employee — set it here if needed. If HR later provides one, it takes over on the next sync.</p>
                </x-admin.field>
                <x-admin.field label="Birthday">
                    <input type="date" wire:model="form.birthday" class="input">
                </x-admin.field>
                <x-admin.field label="Viber Number">
                    <input type="text" wire:model="form.viber_number" class="input">
                </x-admin.field>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Telephone">
                        <input type="text" wire:model="form.telephone" placeholder="e.g. (02) 8888 1000" class="input">
                    </x-admin.field>
                    <x-admin.field label="Local Extension">
                        <input type="text" wire:model="form.local_extension" placeholder="e.g. 2231" class="input">
                    </x-admin.field>
                </div>
                <x-admin.field label="Office Location">
                    <select wire:model="form.office_location_id" class="input">
                        <option value="">— None —</option>
                        @foreach ($officeOptions as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.field label="About Me">
                    <textarea wire:model="form.about_me" rows="3" placeholder="A short bio shown on the public profile…" class="input"></textarea>
                </x-admin.field>
            </div>
        </x-admin.drawer>
    @endif

    @if ($showImportModal)
        <div class="fixed inset-0 z-100 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" wire:click="closeImportModal"></div>
            <div class="relative flex w-full max-w-md flex-col gap-4 rounded-panel bg-bg p-6 shadow-popover">
                <div class="flex items-center gap-3">
                    <h3 class="flex-1 font-display text-base">Import CSV</h3>
                    <button type="button" wire:click="closeImportModal" class="rounded-lg p-1.5 text-ink-secondary hover:bg-surface hover:text-ink"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <p class="text-xs text-ink-secondary">Upload the same file Export CSV produced, with email/Viber/telephone/extension/office/birthday/about-me filled in. Rows are matched by <code class="rounded bg-surface px-1 py-0.5">employee_id</code> — blank cells are left untouched, and everything HR-owned (name, company, department, designation) is ignored even if it was edited in the file.</p>

                <input type="file" wire:model="importFile" accept=".csv,text/csv" class="input">
                @error('importFile') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                <div wire:loading wire:target="importFile,runImport" class="text-xs text-ink-tertiary">Processing…</div>

                @if ($importSummary)
                    <div class="flex flex-col gap-2 rounded-card border border-line p-3.5">
                        <p class="text-sm font-semibold">
                            {{ $importSummary['rowsProcessed'] }} row{{ $importSummary['rowsProcessed'] === 1 ? '' : 's' }} processed ·
                            {{ $importSummary['employeesUpdated'] }} employee{{ $importSummary['employeesUpdated'] === 1 ? '' : 's' }} updated
                        </p>
                        @if (count($importSummary['warnings']) > 0)
                            <div class="max-h-48 overflow-y-auto">
                                <p class="mb-1 text-[11px] font-bold tracking-wide text-warning uppercase">Warnings ({{ count($importSummary['warnings']) }})</p>
                                <ul class="flex flex-col gap-1 text-xs text-ink-secondary">
                                    @foreach ($importSummary['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end gap-2.5">
                    <button type="button" wire:click="closeImportModal" class="btn-secondary">Close</button>
                    <button type="button" wire:click="runImport" wire:loading.attr="disabled" wire:target="runImport" class="btn-primary">
                        <i class="fa-solid fa-file-import"></i>Import
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
