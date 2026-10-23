<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials don\'t match our records.',
            ]);
        }

        request()->session()->regenerate();
        Auth::user()->update(['last_login_at' => now()]);

        // Only staff authenticate — land them in the part of the admin area their
        // role can actually use. See architecture-plan.md §2.4.
        $this->redirect(Auth::user()->homeUrl(), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
