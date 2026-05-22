<?php

namespace App\Livewire;

use App\Enums\ApplicationStatus;
use App\Enums\LeadStatus;
use App\Models\FinanceApplication;
use App\Models\Lead;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'newLeads' => Lead::query()->where('status', LeadStatus::New)->count(),
            'priorityLeads' => Lead::query()->where('is_priority', true)->count(),
            'pendingReview' => FinanceApplication::query()->where('status', ApplicationStatus::UnderReview)->count(),
            'recentLeads' => Lead::query()->with('assignedUser')->latest()->limit(5)->get(),
        ]);
    }
}
