<?php

namespace App\Livewire\Audit;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function render(AuditLogRepositoryInterface $audit)
    {
        return view('livewire.audit.audit-log-index', [
            'logs' => $audit->paginate(20),
        ]);
    }
}
