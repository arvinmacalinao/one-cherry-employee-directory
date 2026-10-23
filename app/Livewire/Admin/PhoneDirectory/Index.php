<?php

namespace App\Livewire\Admin\PhoneDirectory;

use App\Models\PhoneBuilding;
use App\Models\PhoneEntry;
use App\Models\PhoneFloor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Editor for the public phone directory — the only screen an Admin (as opposed
 * to a Super Admin) can reach. Buildings and floors are edited in the drawer;
 * lines are edited inline, row by row, since that's most of the day-to-day work.
 */
class Index extends Component
{
    #[Url(as: 'building')]
    public ?int $buildingId = null;

    public ?string $flash = null;

    // Drawer — shared by the building and floor forms (the drawer component
    // always calls save()/closeForm()).
    public bool $showForm = false;

    public ?string $formType = null;

    public ?int $editingId = null;

    public array $buildingForm = ['name' => '', 'trunkline' => '', 'mobile' => '', 'last_updated_on' => ''];

    public array $floorForm = ['name' => '', 'access_code' => ''];

    // Inline line editor — either editing an existing line or adding one to a floor.
    public ?int $editingEntryId = null;

    public ?int $addingToFloorId = null;

    public array $entryForm = ['department' => '', 'name' => '', 'local' => '', 'is_highlighted' => false];

    public function selectBuilding(int $id): void
    {
        $this->buildingId = $id;
        $this->cancelEntry();
    }

    // ── Buildings ──────────────────────────────────────────────────────────

    public function openCreateBuilding(): void
    {
        $this->resetForm();
        $this->formType = 'building';
        $this->showForm = true;
    }

    public function openEditBuilding(int $id): void
    {
        $building = PhoneBuilding::findOrFail($id);

        $this->resetForm();
        $this->formType = 'building';
        $this->editingId = $building->id;
        $this->buildingForm = [
            'name' => $building->name,
            'trunkline' => $building->trunkline ?? '',
            'mobile' => $building->mobile ?? '',
            'last_updated_on' => $building->last_updated_on?->toDateString() ?? '',
        ];
        $this->showForm = true;
    }

    public function deleteBuilding(int $id): void
    {
        PhoneBuilding::findOrFail($id)->delete();

        $this->buildingId = null;
        $this->closeForm();
        $this->flash = 'Building deleted';
    }

    public function moveBuilding(int $id, int $direction): void
    {
        $this->move(PhoneBuilding::orderBy('sort_order')->orderBy('id')->get(), $id, $direction);
    }

    // ── Floors ─────────────────────────────────────────────────────────────

    public function openCreateFloor(): void
    {
        $this->resetForm();
        $this->formType = 'floor';
        $this->showForm = true;
    }

    public function openEditFloor(int $id): void
    {
        $floor = PhoneFloor::findOrFail($id);

        $this->resetForm();
        $this->formType = 'floor';
        $this->editingId = $floor->id;
        $this->floorForm = ['name' => $floor->name, 'access_code' => $floor->access_code ?? ''];
        $this->showForm = true;
    }

    public function deleteFloor(int $id): void
    {
        PhoneFloor::findOrFail($id)->delete();

        $this->closeForm();
        $this->flash = 'Floor deleted';
    }

    public function moveFloor(int $id, int $direction): void
    {
        $floor = PhoneFloor::findOrFail($id);
        $this->move($floor->building->floors()->get(), $id, $direction);
    }

    // ── Drawer ─────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->formType === 'building' ? $this->saveBuilding() : $this->saveFloor();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function saveBuilding(): void
    {
        $data = $this->validate([
            'buildingForm.name' => ['required', 'string', 'max:255'],
            'buildingForm.trunkline' => ['nullable', 'string', 'max:255'],
            'buildingForm.mobile' => ['nullable', 'string', 'max:255'],
            'buildingForm.last_updated_on' => ['nullable', 'date'],
        ], [], [
            'buildingForm.name' => 'building name',
            'buildingForm.last_updated_on' => 'last update',
        ])['buildingForm'];
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingId) {
            PhoneBuilding::findOrFail($this->editingId)->update($data);
            $this->flash = 'Building updated';
        } else {
            $data['last_updated_on'] ??= today();
            $building = PhoneBuilding::create($data + ['sort_order' => (PhoneBuilding::max('sort_order') ?? 0) + 1]);
            $this->buildingId = $building->id;
            $this->flash = 'Building added';
        }

        $this->closeForm();
    }

    protected function saveFloor(): void
    {
        $data = $this->validate([
            'floorForm.name' => ['required', 'string', 'max:255'],
            'floorForm.access_code' => ['nullable', 'string', 'max:255'],
        ], [], [
            'floorForm.name' => 'floor name',
            'floorForm.access_code' => 'access code',
        ])['floorForm'];
        $data['access_code'] = $data['access_code'] ?: null;

        if ($this->editingId) {
            PhoneFloor::findOrFail($this->editingId)->update($data);
            $this->flash = 'Floor updated';
        } else {
            $building = PhoneBuilding::findOrFail($this->buildingId);
            $building->floors()->create($data + ['sort_order' => ($building->floors()->max('sort_order') ?? 0) + 1]);
            $this->flash = 'Floor added';
        }

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->formType = null;
        $this->editingId = null;
        $this->buildingForm = ['name' => '', 'trunkline' => '', 'mobile' => '', 'last_updated_on' => ''];
        $this->floorForm = ['name' => '', 'access_code' => ''];
        $this->resetErrorBag();
    }

    // ── Lines ──────────────────────────────────────────────────────────────

    public function startAddEntry(int $floorId): void
    {
        $this->cancelEntry();
        $this->addingToFloorId = PhoneFloor::findOrFail($floorId)->id;
    }

    public function startEditEntry(int $id): void
    {
        $entry = PhoneEntry::findOrFail($id);

        $this->cancelEntry();
        $this->editingEntryId = $entry->id;
        $this->entryForm = [
            'department' => $entry->department ?? '',
            'name' => $entry->name ?? '',
            'local' => $entry->local ?? '',
            'is_highlighted' => $entry->is_highlighted,
        ];
    }

    public function saveEntry(): void
    {
        $data = $this->validate([
            'entryForm.department' => ['nullable', 'string', 'max:255', 'required_without_all:entryForm.name,entryForm.local'],
            'entryForm.name' => ['nullable', 'string', 'max:255'],
            'entryForm.local' => ['nullable', 'string', 'max:255'],
            'entryForm.is_highlighted' => ['boolean'],
        ], [
            'entryForm.department.required_without_all' => 'Fill in at least one field.',
        ])['entryForm'];
        $highlighted = (bool) $data['is_highlighted'];
        $data = array_map(fn ($v) => trim((string) $v) === '' ? null : trim($v), Arr::except($data, 'is_highlighted'));
        $data['is_highlighted'] = $highlighted;

        if ($this->editingEntryId) {
            PhoneEntry::findOrFail($this->editingEntryId)->update($data);
            $this->flash = 'Line updated';
        } else {
            $floor = PhoneFloor::findOrFail($this->addingToFloorId);
            $floor->entries()->create($data + ['sort_order' => ($floor->entries()->max('sort_order') ?? 0) + 1]);
            $this->flash = 'Line added';
        }

        $this->cancelEntry();
    }

    /**
     * Typing a manager/VP title into a new line pre-ticks the highlight; the
     * admin can still untick it. Existing lines are never changed behind their back.
     */
    public function updatedEntryFormDepartment(string $value): void
    {
        if ($this->addingToFloorId && PhoneEntry::looksLikeManager($value)) {
            $this->entryForm['is_highlighted'] = true;
        }
    }

    public function toggleHighlight(int $id): void
    {
        $entry = PhoneEntry::findOrFail($id);
        $entry->update(['is_highlighted' => ! $entry->is_highlighted]);
    }

    public function cancelEntry(): void
    {
        $this->editingEntryId = null;
        $this->addingToFloorId = null;
        $this->entryForm = ['department' => '', 'name' => '', 'local' => '', 'is_highlighted' => false];
        $this->resetErrorBag();
    }

    public function deleteEntry(int $id): void
    {
        PhoneEntry::findOrFail($id)->delete();

        $this->cancelEntry();
        $this->flash = 'Line deleted';
    }

    public function moveEntry(int $id, int $direction): void
    {
        $entry = PhoneEntry::findOrFail($id);
        $this->move($entry->floor->entries()->get(), $id, $direction);
    }

    /**
     * Swap a record with its neighbour, renumbering the whole list so legacy or
     * duplicate sort_order values can't make a move silently do nothing.
     */
    protected function move($siblings, int $id, int $direction): void
    {
        $siblings = $siblings->values();
        $index = $siblings->search(fn ($s) => $s->id === $id);
        $target = $index === false ? null : $index + ($direction < 0 ? -1 : 1);

        if ($target === null || ! isset($siblings[$target])) {
            return;
        }

        [$siblings[$index], $siblings[$target]] = [$siblings[$target], $siblings[$index]];

        DB::transaction(function () use ($siblings) {
            foreach ($siblings as $position => $sibling) {
                if ($sibling->sort_order !== $position + 1) {
                    $sibling->update(['sort_order' => $position + 1]);
                }
            }
        });
    }

    public function render()
    {
        $buildings = PhoneBuilding::orderBy('sort_order')->orderBy('id')->get();
        $building = $buildings->firstWhere('id', $this->buildingId) ?? $buildings->first();
        $this->buildingId = $building?->id;

        $floors = $building ? $building->floors()->with('entries')->get() : collect();

        return view('livewire.admin.phone-directory.index', [
            'buildings' => $buildings,
            'building' => $building,
            'floors' => $floors,
            'departmentOptions' => $floors->flatMap->entries->pluck('department')->filter()->unique()->sort()->values(),
        ])->layout('layouts.admin', ['header' => 'Phone Directory']);
    }
}
