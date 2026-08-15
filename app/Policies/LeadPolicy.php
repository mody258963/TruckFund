<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use App\Services\UserHierarchyService;

class LeadPolicy
{
    public function __construct(protected UserHierarchyService $hierarchy) {}

    public function viewAny(User $user): bool
    {
        return $user->role->isCrmRole() || $user->role === UserRole::FinanceOfficer;
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->role === UserRole::FinanceOfficer) {
            return $lead->customer_id !== null;
        }

        return $user->role->isCrmRole() && $this->hierarchy->canViewLead($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || $user->role === UserRole::Manager
            || $user->role === UserRole::TeamLeader
            || $user->role === UserRole::SalesAgent;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead) && (
            $user->role->canAssignLeads()
            || ($user->isSales() && $lead->assigned_user_id === $user->user_id)
        );
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->role->canAssignLeads() && $this->hierarchy->canViewLead($user, $lead);
    }

    public function bulkAssign(User $user): bool
    {
        return $user->isAdmin() || $user->role === UserRole::Manager;
    }

    public function requestTransfer(User $user, Lead $lead): bool
    {
        return $user->role->canRequestLeadTransfer()
            && $lead->assigned_user_id === $user->user_id;
    }
}
