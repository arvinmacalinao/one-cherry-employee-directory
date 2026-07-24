<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Settings extends Component
{
    public string $app_name;

    public string $support_email;

    public string $timezone;

    public string $hr_sync_schedule;

    public ?string $flash = null;

    public function mount(): void
    {
        $this->app_name = Setting::get('app_name', config('app.name'));
        $this->support_email = Setting::get('support_email', 'directory-support@onecherry.group');
        $this->timezone = Setting::get('timezone', 'Asia/Manila');
        $this->hr_sync_schedule = Setting::get('hr_sync_schedule', config('hr_sync.schedule'));
    }

    protected function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email'],
            'timezone' => ['required', 'string'],
            'hr_sync_schedule' => ['required', 'in:hourly,nightly'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('app_name', $this->app_name);
        Setting::set('support_email', $this->support_email);
        Setting::set('timezone', $this->timezone);
        Setting::set('hr_sync_schedule', $this->hr_sync_schedule);

        $this->flash = 'Settings saved';
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'roles' => Role::with('permissions')->get(),
        ])->layout('layouts.admin', ['header' => 'Settings']);
    }
}
