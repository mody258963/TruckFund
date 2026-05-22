<?php

namespace App\Livewire;

use App\Services\GlobalSearchService;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public function render(GlobalSearchService $search)
    {
        return view('livewire.global-search', [
            'results' => strlen($this->query) >= 2 ? $search->search($this->query)->all() : [],
        ]);
    }
}
