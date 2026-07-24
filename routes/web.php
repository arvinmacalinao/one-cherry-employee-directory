<?php

use App\Livewire\Admin\AuditLogs\Index as AdminAuditIndex;
use App\Livewire\Admin\Companies\Index as AdminCompaniesIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Departments\Index as AdminDepartmentsIndex;
use App\Livewire\Admin\Designations\Index as AdminDesignationsIndex;
use App\Livewire\Admin\Employees\Index as AdminEmployeesIndex;
use App\Livewire\Admin\Offices\Index as AdminOfficesIndex;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Sync as AdminSync;
use App\Livewire\Auth\Login;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Companies\Show as CompaniesShow;
use App\Livewire\Dashboard;
use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Directory\Index as DirectoryIndex;
use App\Livewire\EmployeeProfile;
use App\Livewire\Favorites\Index as FavoritesIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/directory', DirectoryIndex::class)->name('directory.index');
    Route::get('/directory/{employee}', EmployeeProfile::class)->name('directory.show');

    Route::get('/companies', CompaniesIndex::class)->name('companies.index');
    Route::get('/companies/{company}', CompaniesShow::class)->name('companies.show');
    Route::get('/departments', DepartmentsIndex::class)->name('departments.index');
    Route::get('/favorites', FavoritesIndex::class)->name('favorites.index');

    Route::get('/profile', function () {
        return redirect()->route('directory.show', auth()->user()->employee_id);
    })->name('profile.me');
});

Route::middleware(['auth', 'can:manage employees'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/employees', AdminEmployeesIndex::class)->name('employees.index');
});

Route::middleware(['auth', 'can:manage companies'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/companies', AdminCompaniesIndex::class)->name('companies.index');
});

Route::middleware(['auth', 'can:manage departments'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/departments', AdminDepartmentsIndex::class)->name('departments.index');
});

Route::middleware(['auth', 'can:manage designations'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/designations', AdminDesignationsIndex::class)->name('designations.index');
});

Route::middleware(['auth', 'can:manage office locations'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/office-locations', AdminOfficesIndex::class)->name('offices.index');
});

Route::middleware(['auth', 'can:run hr sync'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sync', AdminSync::class)->name('sync');
});

Route::middleware(['auth', 'can:manage settings'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings', AdminSettings::class)->name('settings');
});

Route::middleware(['auth', 'can:view audit logs'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/audit-logs', AdminAuditIndex::class)->name('audit.index');
});
