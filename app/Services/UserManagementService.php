<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(protected UserHierarchyService $hierarchy) {}

    public function create(User $creator, array $data): User
    {
        $role = UserRole::from((int) $data['role']);
        $this->assertCanCreateRole($creator, $role);
        $this->assertReportsTo($creator, $role, $data['reports_to_user_id'] ?? null);

        return User::query()->create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password'] ?? 'password'),
            'role' => $role,
            'reports_to_user_id' => $data['reports_to_user_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(User $creator, User $target, array $data): User
    {
        if (! $this->hierarchy->canViewUser($creator, $target) && ! $creator->isAdmin()) {
            throw ValidationException::withMessages(['user' => __('crm.users.cannot_edit')]);
        }

        if (isset($data['role'])) {
            $role = UserRole::from((int) $data['role']);
            $this->assertCanCreateRole($creator, $role);
        }

        if (array_key_exists('reports_to_user_id', $data)) {
            $this->assertReportsTo($creator, $target->role, $data['reports_to_user_id']);
        }

        $payload = collect($data)->only(['full_name', 'email', 'phone', 'role', 'reports_to_user_id', 'is_active'])->filter()->all();

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $target->update($payload);

        return $target->fresh();
    }

    protected function assertCanCreateRole(User $creator, UserRole $role): void
    {
        if ($creator->isAdmin()) {
            return;
        }

        if ($creator->role === UserRole::Manager && in_array($role, [UserRole::TeamLeader, UserRole::SalesAgent], true)) {
            return;
        }

        if ($creator->role === UserRole::TeamLeader && $role === UserRole::SalesAgent) {
            return;
        }

        throw ValidationException::withMessages(['role' => __('crm.users.invalid_role')]);
    }

    protected function assertReportsTo(User $creator, UserRole $role, ?string $reportsToId): void
    {
        if ($role === UserRole::Admin) {
            return;
        }

        if ($role === UserRole::Manager && ! $creator->isAdmin()) {
            throw ValidationException::withMessages(['reports_to' => __('crm.users.manager_admin_only')]);
        }

        if (in_array($role, [UserRole::TeamLeader, UserRole::SalesAgent], true) && ! $reportsToId) {
            throw ValidationException::withMessages(['reports_to' => __('crm.users.reports_to_required')]);
        }

        if (in_array($role, [UserRole::FinanceOfficer, UserRole::MerchantAgent], true)) {
            return;
        }

        if ($reportsToId && ! $this->hierarchy->canViewUser($creator, User::query()->findOrFail($reportsToId))) {
            if (! $creator->isAdmin()) {
                throw ValidationException::withMessages(['reports_to' => __('crm.users.invalid_manager')]);
            }
        }
    }
}
