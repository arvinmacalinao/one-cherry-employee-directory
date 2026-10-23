{{--
    One building's sheet, laid out like the printed "Local Phone Numbers" page:
    two columns of floors, each column with its own header row.
    Expects: $floors (with entries loaded), $directory (PhoneDirectoryService).
--}}
<div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2 print:grid-cols-2 print:gap-3">
    @foreach ($directory->columns($floors) as $column)
        @if ($column->isNotEmpty())
            <div class="overflow-x-auto rounded-card border border-line-strong bg-surface-raised print:overflow-visible print:rounded-none">
                <table class="w-full min-w-[420px] table-fixed border-collapse text-[13px] print:min-w-0 print:text-[9.5pt]">
                    <colgroup>
                        <col class="w-[42%]">
                        <col class="w-[38%]">
                        <col class="w-[20%]">
                    </colgroup>
                    <thead>
                        <tr class="phone-sheet-head text-[10.5px] font-bold tracking-wide uppercase print:text-[7.5pt]">
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
                                        <span class="font-display text-sm font-bold tracking-wide uppercase print:text-[10pt]">{{ $floor->name }}</span>
                                        @if ($floor->access_code)
                                            <span class="ml-auto text-[10.5px] font-semibold tracking-wide uppercase opacity-80 print:text-[7.5pt]">Access code – {{ $floor->access_code }}</span>
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
