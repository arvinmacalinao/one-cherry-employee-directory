<?php

namespace App\Livewire\Companies;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public function render(CompanyRepositoryInterface $companies)
    {
        $term = trim($this->search);

        $list = $companies->allActiveWithCounts()
            ->when($term !== '', fn ($collection) => $collection->filter(
                fn ($company) => str_contains(strtolower($company->name), strtolower($term))
            ))
            ->values();

        return view('livewire.companies.index', [
            'companies' => $list,
        ])->layout('layouts.app', ['header' => 'Companies']);
    }
}
