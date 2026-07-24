<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\FakeHrSource;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HrSourceInterface::class, function () {
            return match (config('hr_sync.source')) {
                // 'rest_api' => new HrRestApiSource(config('hr_sync.api.base_url'), config('hr_sync.api.api_key')),
                default => new FakeHrSource,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $view->with('unmappedCount',
                Company::unmapped()->count() + Department::unmapped()->count() + Designation::unmapped()->count()
            );
        });
    }
}
