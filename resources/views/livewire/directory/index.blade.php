<div class="flex flex-col gap-5">
    <div class="sticky top-15 z-10 flex flex-col gap-3 border-b border-line bg-bg py-3.5">
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="flex min-w-[220px] flex-1 items-center gap-2 rounded-control border border-line bg-surface-raised px-3 py-2">
                <i class="fa-solid fa-magnifying-glass text-ink-tertiary"></i>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search employees…" class="w-full border-0 bg-transparent text-sm outline-none">
            </div>

            <select wire:model.live="company" class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm">
                <option value="">All Companies</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="department" class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm">
                <option value="">All Departments</option>
                @foreach ($departmentOptions as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="active">Active Employee</option>
                <option value="on_leave">On Leave</option>
            </select>

            <select wire:model.live="sort" class="rounded-control border border-line bg-surface-raised px-3 py-2 text-sm">
                <option value="newest">Sort: Newest</option>
                <option value="name">Sort: Name A–Z</option>
                <option value="department">Sort: Department</option>
                <option value="company">Sort: Company</option>
            </select>
        </div>
        <p class="text-xs text-ink-tertiary" wire:loading.remove>{{ $employees->total() }} employee{{ $employees->total() === 1 ? '' : 's' }} found</p>
    </div>

    <div wire:loading.class="opacity-50" class="grid grid-cols-1 gap-4 transition-opacity sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($employees as $employee)
            <div class="card flex flex-col gap-3.5 p-5 transition-all duration-200 hover:-translate-y-1 hover:shadow-raised">
                <div class="flex items-start gap-3">
                    <span class="flex h-11.5 w-11.5 flex-shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-sm font-bold text-brand">
                        {{ collect(explode(' ', $employee->full_name))->map(fn ($p) => $p[0] ?? '')->take(2)->implode('') }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-sm font-bold">{{ $employee->full_name }}</p>
                        <p class="truncate text-xs font-semibold text-brand">{{ $employee->designation->name }}</p>
                        <p class="truncate text-xs text-ink-secondary">{{ $employee->department->name }} · {{ $employee->company->name }}</p>
                    </div>
                </div>
                <div class="flex flex-col gap-1.5 border-t border-line pt-3 text-xs text-ink-secondary">
                    <div class="flex items-center gap-2 truncate"><i class="fa-solid fa-envelope w-3.5 text-ink-tertiary"></i>{{ $employee->email }}</div>
                    @if ($employee->profile?->mobile_number)
                        <div class="flex items-center gap-2 truncate"><i class="fa-solid fa-phone w-3.5 text-ink-tertiary"></i>{{ $employee->profile->mobile_number }}</div>
                    @endif
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('directory.show', $employee) }}" wire:navigate class="btn-primary flex-1 justify-center !py-1.5 text-xs">View Profile</a>
                    <a href="mailto:{{ $employee->email }}" class="btn-secondary !px-2.5 !py-1.5"><i class="fa-solid fa-envelope"></i></a>
                    @if ($employee->profile?->mobile_number)
                        <a href="tel:{{ $employee->profile->mobile_number }}" class="btn-secondary !px-2.5 !py-1.5"><i class="fa-solid fa-phone"></i></a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full">
                @include('livewire.partials.coming-soon', [
                    'icon' => 'fa-magnifying-glass',
                    'title' => 'No employees match this view',
                    'description' => 'Try a different search term or clear your filters.',
                ])
            </div>
        @endforelse
    </div>

    <div>{{ $employees->links() }}</div>
</div>
