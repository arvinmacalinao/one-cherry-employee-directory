<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Staff accounts — Super Admins create Admins here (phone directory editors) or
 * other Super Admins. There is still no employee account of any kind.
 */
class Index extends Component
{
    public const ROLES = ['Super Admin', 'Admin'];

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'email' => '', 'role' => 'Admin', 'password' => '', 'is_active' => true,
    ];

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'form.role' => ['required', Rule::in(self::ROLES)],
            'form.password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'form.is_active' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => 'name',
            'form.email' => 'email',
            'form.role' => 'role',
            'form.password' => 'password',
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first() ?? 'Admin',
            'password' => '',
            'is_active' => $user->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate()['form'];

        // Signing yourself out of your own Super Admin access would lock the
        // account out of this page mid-edit — another Super Admin has to do it.
        if ($this->editingId === auth()->id()) {
            $validated['role'] = 'Super Admin';
            $validated['is_active'] = true;
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ];
        if (filled($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($attributes);
            $this->flash = 'User updated';
        } else {
            $user = User::create($attributes);
            $this->flash = 'User created';
        }

        $user->syncRoles([$validated['role']]);

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '', 'email' => '', 'role' => 'Admin', 'password' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render()
    {
        $term = trim($this->search);

        $users = User::with('roles')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
        ])->layout('layouts.admin', ['header' => 'Users']);
    }
}
