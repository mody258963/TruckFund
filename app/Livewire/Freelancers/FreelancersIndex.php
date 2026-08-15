<?php

namespace App\Livewire\Freelancers;

use App\Models\Freelancer;
use App\Services\FreelancerService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class FreelancersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreate = false;

    public string $full_name = '';

    public string $phone = '';

    public string $national_id = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Freelancer::class);
    }

    public function create(FreelancerService $service): void
    {
        $this->authorize('create', Freelancer::class);
        $this->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'national_id' => 'nullable|digits:14',
        ]);
        $service->create([
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'national_id' => $this->national_id ?: null,
        ], auth()->user());
        $this->reset(['full_name', 'phone', 'national_id', 'showCreate']);
        $this->resetPage();
        $this->dispatch('notify', message: __('common.saved'));
    }

    public function render()
    {
        $query = Freelancer::query()->orderByDesc('created_at');

        if ($this->search) {
            $s = '%'.$this->search.'%';
            $query->where(fn ($q) => $q
                ->where('full_name', 'like', $s)
                ->orWhere('phone', 'like', $s)
                ->orWhere('national_id', 'like', $s));
        }

        return view('livewire.freelancers.freelancers-index', [
            'freelancers' => $query->paginate(15),
        ]);
    }
}
