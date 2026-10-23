<?php

namespace App\Services;

use App\Models\PhoneBuilding;
use App\Models\PhoneFloor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shapes the phone directory for display so it reads like the printed sheet:
 * consecutive rows with the same department (or the same local) merge into one
 * cell, and a building's floors split into two balanced columns.
 */
class PhoneDirectoryService
{
    public function buildings(): Collection
    {
        return PhoneBuilding::orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Floors of one building (or, when searching, of every building) with their
     * entries loaded — filtered down to matching lines when a term is given.
     * Floors with no matching lines are dropped from search results.
     */
    public function floors(?PhoneBuilding $building, string $term = ''): Collection
    {
        $term = trim($term);
        $matches = function ($q) use ($term) {
            $q->where(fn (Builder $w) => $w
                ->where('department', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('local', 'like', "%{$term}%"));
        };

        return PhoneFloor::query()
            ->with(['building', 'entries' => fn ($q) => $term === '' ? $q : $matches($q)])
            ->when($building, fn ($q) => $q->where('phone_building_id', $building->id))
            ->when($term !== '', fn ($q) => $q->whereHas('entries', $matches))
            ->join('phone_buildings', 'phone_buildings.id', '=', 'phone_floors.phone_building_id')
            ->orderBy('phone_buildings.sort_order')
            ->orderBy('phone_buildings.id')
            ->orderBy('phone_floors.sort_order')
            ->orderBy('phone_floors.id')
            ->select('phone_floors.*')
            ->get();
    }

    /**
     * One row per entry with rowspans for the department and local cells:
     * a span of 0 means "covered by the row above, don't render this cell".
     */
    public function rows(Collection $entries): array
    {
        $entries = $entries->values();
        $rows = [];

        foreach ($entries as $i => $entry) {
            $rows[] = [
                'entry' => $entry,
                'departmentSpan' => $this->span($entries, $i, 'department'),
                'localSpan' => $this->span($entries, $i, 'local'),
            ];
        }

        return $rows;
    }

    /**
     * Splits floors into two columns so both sides of the sheet end up roughly
     * the same height, keeping floor order (left column first, like the paper).
     *
     * @return array{0: Collection, 1: Collection}
     */
    public function columns(Collection $floors): array
    {
        $heights = $floors->map(fn (PhoneFloor $floor) => $floor->entries->count() + 1)->values();
        $total = $heights->sum();

        $best = $floors->count();
        $bestHeight = PHP_INT_MAX;
        $left = 0;

        for ($split = 0; $split <= $floors->count(); $split++) {
            $tallest = max($left, $total - $left);
            if ($tallest < $bestHeight) {
                $best = $split;
                $bestHeight = $tallest;
            }
            $left += $heights[$split] ?? 0;
        }

        return [$floors->take($best)->values(), $floors->slice($best)->values()];
    }

    protected function span(Collection $entries, int $index, string $field): int
    {
        $key = $this->normalize($entries[$index]->{$field});

        if ($key === '') {
            return 1;
        }

        if ($index > 0 && $this->normalize($entries[$index - 1]->{$field}) === $key) {
            return 0;
        }

        $span = 1;
        while (isset($entries[$index + $span]) && $this->normalize($entries[$index + $span]->{$field}) === $key) {
            $span++;
        }

        return $span;
    }

    protected function normalize(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}
