<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserHierarchyService;
use App\Services\UserManagementService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UsersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreate = false;

    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public int $role = UserRole::SalesAgent->value;

    public ?string $reports_to_user_id = null;

    public string $password = 'password';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function create(UserManagementService $service): void
    {
        $this->authorize('create', User::class);
        $this->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|integer',
            'password' => 'required|min:6',
        ]);
        $service->create(auth()->user(), [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'reports_to_user_id' => $this->reports_to_user_id,
            'password' => $this->password,
        ]);
        $this->reset(['full_name', 'email', 'phone', 'password', 'showCreate']);
        $this->resetPage();
        $this->dispatch('notify', message: __('common.saved'));
    }

    public function render(UserHierarchyService $hierarchy)
    {
        $viewer = auth()->user();
        $query = User::query()->with('manager')->where('is_active', true);

        if (! $viewer->isAdmin()) {
            $query->whereIn('user_id', $hierarchy->subordinateIds($viewer, true));
        }

        if ($this->search) {
            $s = '%'.$this->search.'%';
            $query->where(fn ($q) => $q->where('full_name', 'like', $s)->orWhere('email', 'like', $s));
        }

        $managers = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Admin->value, UserRole::Manager->value, UserRole::TeamLeader->value])
            ->orderBy('full_name')
            ->get();

        $creatableRoles = match ($viewer->role) {
            UserRole::Admin => [UserRole::Manager, UserRole::TeamLeader, UserRole::SalesAgent, UserRole::FinanceOfficer, UserRole::MerchantAgent],
            UserRole::Manager => [UserRole::TeamLeader, UserRole::SalesAgent],
            UserRole::TeamLeader => [UserRole::SalesAgent],
            default => [],
        };

        return view('livewire.users.users-index', [
            'users' => $query->orderBy('full_name')->paginate(15),
            'managers' => $managers,
            'creatableRoles' => $creatableRoles,
        ]);
    }
}
