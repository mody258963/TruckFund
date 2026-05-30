<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserHierarchyService
{
    public function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** @return Collection<int, string> */
    public function subordinateIds(User $user, bool $includeSelf = false): Collection
    {
        if ($this->isAdmin($user)) {
            $ids = User::query()->where('is_active', true)->pluck('user_id');

            return $includeSelf ? $ids : $ids->reject(fn ($id) => $id === $user->user_id)->values();
        }

        $ids = collect($includeSelf ? [$user->user_id] : []);

        $direct = User::query()
            ->where('reports_to_user_id', $user->user_id)
            ->where('is_active', true)
            ->pluck('user_id');

        foreach ($direct as $childId) {
            $ids->push($childId);
            $child = User::query()->find($childId);
            if ($child) {
                $ids = $ids->merge($this->subordinateIds($child, true));
            }
        }

        return $ids->unique()->values();
    }

    public function canViewUser(User $viewer, User $target): bool
    {
        if ($this->isAdmin($viewer)) {
            return true;
        }

        if ($viewer->user_id === $target->user_id) {
            return true;
        }

        return $this->subordinateIds($viewer, true)->contains($target->user_id);
    }

    public function canViewLead(User $viewer, Lead $lead): bool
    {
        if ($this->isAdmin($viewer)) {
            return true;
        }

        if ($lead->assigned_user_id === null) {
            return $viewer->role->canAssignLeads();
        }

        return $this->subordinateIds($viewer, true)->contains($lead->assigned_user_id);
    }

    public function canAssignLeadTo(User $assigner, User $assignee): bool
    {
        if (! $assigner->role->canAssignLeads()) {
            return false;
        }

        if ($this->isAdmin($assigner)) {
            return $assignee->role->isCrmRole() && $assignee->is_active;
        }

        if ($assigner->role === UserRole::Manager) {
            return $this->subordinateIds($assigner, true)->contains($assignee->user_id)
                && in_array($assignee->role, [UserRole::TeamLeader, UserRole::SalesAgent], true);
        }

        if ($assigner->role === UserRole::TeamLeader) {
            return $assignee->reports_to_user_id === $assigner->user_id
                && $assignee->role === UserRole::SalesAgent;
        }

        return false;
    }

    /** Users the assigner may assign leads to. */
    public function assignableUsersQuery(User $assigner): Builder
    {
        if ($this->isAdmin($assigner)) {
            return User::query()
                ->where('is_active', true)
                ->whereIn('role', [
                    UserRole::Manager->value,
                    UserRole::TeamLeader->value,
                    UserRole::SalesAgent->value,
                ]);
        }

        if ($assigner->role === UserRole::Manager) {
            return User::query()
                ->where('is_active', true)
                ->whereIn('user_id', $this->subordinateIds($assigner, true));
        }

        if ($assigner->role === UserRole::TeamLeader) {
            return User::query()
                ->where('is_active', true)
                ->where('reports_to_user_id', $assigner->user_id)
                ->where('role', UserRole::SalesAgent);
        }

        return User::query()->whereRaw('0 = 1');
    }

    public function defaultReviewerFor(User $sales): ?User
    {
        if ($sales->reports_to_user_id) {
            return User::query()->find($sales->reports_to_user_id);
        }

        return null;
    }

    public function scopeLeadsVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($this->isAdmin($viewer)) {
            return $query;
        }

        $ids = $this->subordinateIds($viewer, true);

        return $query->where(function (Builder $q) use ($ids, $viewer) {
            $q->whereIn('assigned_user_id', $ids)
                ->orWhere(function (Builder $q2) use ($viewer) {
                    if ($viewer->role->canAssignLeads()) {
                        $q2->whereNull('assigned_user_id');
                    }
                });
        });
    }
}
