<?php

namespace App\Livewire\Admin\Announcements;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fully directory-owned — HR has no concept of announcements. Expired rows are
 * never deleted, just filtered out of Home's display query; this list shows
 * everything, including expired, for history. See architecture-plan.md §3.2.
 */
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'title' => '', 'body' => '', 'published_at' => '', 'expires_at' => '', 'is_active' => true,
    ];

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.body' => ['required', 'string'],
            'form.published_at' => ['nullable', 'date'],
            'form.expires_at' => ['nullable', 'date', 'after_or_equal:form.published_at'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->form['published_at'] = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $announcement = Announcement::findOrFail($id);

        $this->editingId = $announcement->id;
        $this->form = [
            'title' => $announcement->title,
            'body' => $announcement->body,
            'published_at' => $announcement->published_at?->format('Y-m-d\TH:i'),
            'expires_at' => $announcement->expires_at?->format('Y-m-d\TH:i'),
            'is_active' => $announcement->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(AnnouncementService $announcements): void
    {
        $validated = $this->validate()['form'];
        $validated['published_at'] = $validated['published_at'] ?: null;
        $validated['expires_at'] = $validated['expires_at'] ?: null;

        if ($this->editingId) {
            $announcements->update(Announcement::findOrFail($this->editingId), $validated);
            $this->flash = 'Announcement updated';
        } else {
            $validated['created_by'] = auth()->id();
            $announcements->create($validated);
            $this->flash = 'Announcement created';
        }

        $this->closeForm();
    }

    public function delete(int $id, AnnouncementService $announcements): void
    {
        $announcements->delete(Announcement::findOrFail($id));
        $this->flash = 'Announcement deleted';
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'title' => '', 'body' => '', 'published_at' => '', 'expires_at' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render(AnnouncementService $announcements)
    {
        return view('livewire.admin.announcements.index', [
            'announcements' => $announcements->paginate(),
        ])->layout('layouts.admin', ['header' => 'Announcements']);
    }
}
