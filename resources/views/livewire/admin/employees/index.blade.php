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
            <option value="active">Active Employee</option>
            <option value="on_leave">On Leave</option>
            <option value="resigned">Resigned</option>
            <option value="inactive">Inactive</option>
        </select>
        <span class="ml-auto text-xs text-ink-tertiary">{{ $employees->total() }} employees</span>
        <button wire:click="openCreate" class="btn-primary text-xs"><i class="fa-solid fa-plus"></i>Add Employee</button>
    </div>

    <div class="table-wrap overflow-x-auto rounded-card border border-line bg-surface-raised">
        <table class="w-full min-w-[860px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line bg-surface text-left text-[10.5px] font-bold tracking-wide text-ink-tertiary uppercase">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Designation</th>
                    <th class="px-4 py-3">Source</th>
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
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->company->name }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->department->name }}</td>
                        <td class="px-4 py-3 text-ink-secondary">{{ $employee->designation->name }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $employee->source->value === 'hr_sync' ? 'bg-surface text-ink-secondary' : 'bg-brand-tint text-brand' }}">
                                {{ $employee->source->value === 'hr_sync' ? 'HR Synced' : 'Manually Added' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $employee->employment_status->value === 'active' ? 'badge-active' : ($employee->employment_status->value === 'on_leave' ? 'badge-leave' : 'badge-inactive') }}">
                                {{ $employee->employment_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('directory.show', $employee) }}" wire:navigate class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink" title="View profile"><i class="fa-solid fa-eye"></i></a>
                            <button wire:click="openEdit({{ $employee->id }})" class="rounded-lg p-2 text-ink-secondary hover:bg-surface hover:text-ink" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-secondary">No employees match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $employees->links() }}</div>

    @if ($showForm)
        <x-admin.drawer :title="$editingId ? 'Edit '.$form['first_name'].' '.$form['last_name'] : 'Add Employee'">
            <div class="mb-4 flex gap-1 border-b border-line">
                @foreach (['personal' => 'Personal', 'contact' => 'Contact', 'organization' => 'Organization', 'additional' => 'Additional'] as $key => $label)
                    <button
                        type="button"
                        wire:click="setTab('{{ $key }}')"
                        class="relative top-px mr-4 border-b-2 px-0.5 py-2 text-sm font-semibold transition-colors duration-200 {{ $activeTab === $key ? 'border-brand text-brand' : 'border-transparent text-ink-secondary hover:text-ink' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            @if ($activeTab === 'personal')
                <div class="mb-5 flex items-center gap-4 border-b border-line pb-5">
                    <div class="h-20 w-20 flex-shrink-0">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 rounded-full border border-line object-cover" alt="Photo preview">
                        @elseif ($editingEmployee?->getFirstMediaUrl('photo', 'thumb'))
                            <img src="{{ $editingEmployee->getFirstMediaUrl('photo', 'thumb') }}" class="h-20 w-20 rounded-full border border-line object-cover" alt="Current photo">
                        @else
                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-tint font-display text-xl font-bold text-brand">
                                {{ collect(explode(' ', trim(($form['first_name'] ?? '').' '.($form['last_name'] ?? ''))))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-admin.field label="Employee ID" :locked="true">
                        <input type="text" value="{{ $editingId ? $form['employee_id'] : 'Auto-generated on save' }}" disabled class="input">
                    </x-admin.field>
                    <div></div>
                    <x-admin.field label="First Name" :locked="$isSynced" :error="$errors->first('form.first_name')">
                        <input type="text" wire:model="form.first_name" @disabled($isSynced) class="input">
                    </x-admin.field>
                    <x-admin.field label="Middle Name">
                        <input type="text" wire:model="form.middle_name" class="input">
                        <p class="text-[11px] text-ink-tertiary">Not provided by HR — set by an Admin.</p>
                    </x-admin.field>
                    <x-admin.field label="Last Name" :locked="$isSynced" :error="$errors->first('form.last_name')">
                        <input type="text" wire:model="form.last_name" @disabled($isSynced) class="input">
                    </x-admin.field>
                    <x-admin.field label="Suffix">
                        <input type="text" wire:model="form.suffix" placeholder="Jr., III, …" class="input">
                    </x-admin.field>
                    <x-admin.field label="Nickname">
                        <input type="text" wire:model="form.nickname" class="input">
                    </x-admin.field>
                    <x-admin.field label="Gender">
                        <select wire:model="form.gender" class="input">
                            <option value="">— Select —</option>
                            <option>Female</option>
                            <option>Male</option>
                            <option>Prefer not to say</option>
                        </select>
                    </x-admin.field>
                    <x-admin.field label="Birthday">
                        <input type="date" wire:model="form.birthday" class="input">
                    </x-admin.field>
                    <x-admin.field label="Pronunciation">
                        <input type="text" wire:model="form.name_pronunciation" placeholder="e.g. an-DRAY-ah" class="input">
                    </x-admin.field>
                </div>
            @endif

            @if ($activeTab === 'contact')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-admin.field label="Corporate Email" :locked="$isSynced" :error="$errors->first('form.email')" :full="true">
                        <input type="text" wire:model="form.email" @disabled($isSynced) class="input">
                    </x-admin.field>
                    <x-admin.field label="Personal Email" :full="true">
                        <input type="text" wire:model="form.personal_email" class="input">
                    </x-admin.field>
                    <x-admin.field label="Mobile Number">
                        <input type="text" wire:model="form.mobile_number" class="input">
                    </x-admin.field>
                    <x-admin.field label="Viber Number">
                        <input type="text" wire:model="form.viber_number" class="input">
                    </x-admin.field>
                    <x-admin.field label="Telephone">
                        <input type="text" wire:model="form.telephone" class="input">
                    </x-admin.field>
                    <x-admin.field label="Local Extension">
                        <input type="text" wire:model="form.local_extension" class="input">
                    </x-admin.field>
                    <x-admin.field label="Office Seat / Desk">
                        <input type="text" wire:model="form.office_seat" placeholder="e.g. BGC-3F-014" class="input">
                    </x-admin.field>
                    <x-admin.field label="Office Location">
                        <select wire:model="form.office_location_id" class="input">
                            <option value="">— None —</option>
                            @foreach ($officeOptions as $office)
                                <option value="{{ $office->id }}">{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>
                </div>
            @endif

            @if ($activeTab === 'organization')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-admin.field label="Company" :locked="$isSynced" :error="$errors->first('form.company_id')">
                        @if ($isSynced)
                            <input type="text" value="{{ $companyOptions->firstWhere('id', (int) $form['company_id'])?->name }}" disabled class="input">
                        @else
                            <select wire:model.live="form.company_id" class="input">
                                <option value="">— Select —</option>
                                @foreach ($companyOptions as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </x-admin.field>
                    <x-admin.field label="Department" :error="$errors->first('form.department_id')">
                        <select wire:model="form.department_id" class="input">
                            <option value="">{{ $departmentOptions->isEmpty() ? '— Select company first —' : '— None —' }}</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-ink-tertiary">Not provided by HR — set by an Admin.</p>
                    </x-admin.field>
                    <x-admin.field label="Designation" :locked="$isSynced" :error="$errors->first('form.designation_id')">
                        @if ($isSynced)
                            <input type="text" value="{{ \App\Models\Designation::find($form['designation_id'])?->name }}" disabled class="input">
                        @else
                            <select wire:model="form.designation_id" class="input">
                                <option value="">— Select company first —</option>
                                @foreach ($designationOptions as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </x-admin.field>
                    <x-admin.field label="Employment Status" :locked="$isSynced">
                        @if ($isSynced)
                            <input type="text" value="{{ \App\Enums\EmploymentStatus::from($form['employment_status'])->label() }}" disabled class="input">
                        @else
                            <select wire:model="form.employment_status" class="input">
                                @foreach (\App\Enums\EmploymentStatus::cases() as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        @endif
                    </x-admin.field>
                    <x-admin.field label="Date Hired">
                        <input type="date" wire:model="form.date_hired" class="input">
                        <p class="text-[11px] text-ink-tertiary">Not provided by HR — set by an Admin.</p>
                    </x-admin.field>
                    <x-admin.field label="Immediate Supervisor">
                        <select wire:model="form.immediate_supervisor_id" class="input">
                            <option value="">— None —</option>
                            @foreach ($supervisorOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-ink-tertiary">Not provided by HR — set by an Admin.</p>
                    </x-admin.field>
                </div>
            @endif

            @if ($activeTab === 'additional')
                <div class="grid grid-cols-1 gap-4">
                    <x-admin.field label="Skills (comma separated)">
                        <input type="text" wire:model="form.skills" placeholder="Figma, Design Systems, User Research" class="input">
                    </x-admin.field>
                    <x-admin.field label="About Me">
                        <textarea wire:model="form.about_me" rows="3" placeholder="A short bio shown on the public profile…" class="input"></textarea>
                    </x-admin.field>
                    <div class="grid grid-cols-2 gap-4">
                        <x-admin.field label="LinkedIn">
                            <input type="text" wire:model="form.linkedin_url" placeholder="linkedin.com/in/…" class="input">
                        </x-admin.field>
                        <x-admin.field label="Facebook">
                            <input type="text" wire:model="form.facebook_url" placeholder="facebook.com/…" class="input">
                        </x-admin.field>
                    </div>
                    <x-admin.field label="Emergency Contact Name">
                        <input type="text" wire:model="form.emergency_contact_name" class="input">
                    </x-admin.field>
                    <div class="grid grid-cols-2 gap-4">
                        <x-admin.field label="Relationship">
                            <input type="text" wire:model="form.emergency_contact_relationship" class="input">
                        </x-admin.field>
                        <x-admin.field label="Emergency Contact Phone">
                            <input type="text" wire:model="form.emergency_contact_phone" class="input">
                        </x-admin.field>
                    </div>
                </div>
            @endif
        </x-admin.drawer>
    @endif
</div>
