{{--
    One building's sheet, laid out like the printed "Local Phone Numbers" page:
    two columns of floors, each column with its own header row.
    Expects: $floors (with entries loaded), $directory (PhoneDirectoryService).
--}}
<div class="phone-sheet-grid grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
    @foreach ($directory->columns($floors) as $column)
        @if ($column->isNotEmpty())
            <div class="phone-sheet-wrap overflow-x-auto rounded-card border border-line-strong bg-surface-raised">
                <table class="phone-sheet-table w-full min-w-[420px] table-fixed border-collapse text-[13px]">
                    <colgroup>
                        <col class="w-[42%]">
                        <col class="w-[38%]">
                        <col class="w-[20%]">
                    </colgroup>
                    <thead>
                        <tr class="phone-sheet-head text-[10.5px] font-bold tracking-wide uppercase">
                            <th class="px-3 py-2 text-left">Company / Department</th>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-center">Local</th>
                        </tr>
                    </thead>
                    @foreach ($column as $floor)
                        <tbody class="break-inside-avoid">
                            <tr class="phone-sheet-floor">
                                <th colspan="3" class="px-3 py-1.5 text-left">
                                    <span class="flex items-baseline gap-3">
                                        <span class="phone-sheet-floor-name font-display text-sm font-bold tracking-wide uppercase">{{ $floor->name }}</span>
                                        @if ($floor->access_code)
                                            <span class="phone-sheet-access ml-auto text-[10.5px] font-semibold tracking-wide uppercase opacity-80">Access code – {{ $floor->access_code }}</span>
                                        @endif
                                    </span>
                                </th>
                            </tr>
                            @forelse ($directory->rows($floor->entries) as $row)
                                <tr @class(['border-b border-line', 'phone-sheet-highlight' => $row['entry']->is_highlighted])>
                                    @if ($row['departmentSpan'] > 0)
                                        <td rowspan="{{ $row['departmentSpan'] }}" class="border-r border-line px-3 py-1.5 align-middle font-semibold uppercase">{{ $row['entry']->department }}</td>
                                    @endif
                                    <td class="border-r border-line px-3 py-1.5 align-middle uppercase">{{ $row['entry']->name }}</td>
                                    @if ($row['localSpan'] > 0)
                                        <td rowspan="{{ $row['localSpan'] }}" class="px-3 py-1.5 text-center align-middle font-bold whitespace-nowrap tabular-nums">{{ $row['entry']->local }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr class="border-b border-line"><td colspan="3" class="px-3 py-2 text-center text-xs text-ink-tertiary">No lines on this floor yet.</td></tr>
                            @endforelse
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    @endforeach
</div>
