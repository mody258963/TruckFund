<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Models\Lead;
use App\Models\User;
use App\Services\UserHierarchyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LeadRepository extends EloquentRepository implements LeadRepositoryInterface
{
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    public function findWithRelations(string $id): ?Lead
    {
        return $this->query()
            ->with([
                'assignedUser',
                'createdBy',
                'freelancer',
                'customer',
                'communicationLogs.user',
                'transferRequests.requestedBy',
                'transferRequests.toUser',
            ])
            ->find($id);
    }

    public function paginate(int $perPage = 15, array $filters = [], ?User $viewer = null): LengthAwarePaginator
    {
        $query = $this->query()->with(['assignedUser', 'createdBy', 'freelancer']);

        if ($viewer) {
            app(UserHierarchyService::class)->scopeLeadsVisibleTo($query, $viewer);
        }

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['value'])) {
            $query->where('value', $filters['value']);
        }
        if (! empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', $filters['assigned_user_id']);
        }
        if (! empty($filters['search'])) {
            $s = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q
                ->where('lead_number', 'like', $s)
                ->orWhere('customer_name', 'like', $s)
                ->orWhere('phone', 'like', $s));
        }
        if (! empty($filters['priority'])) {
            $query->where('is_priority', true);
        }

        return $query;
    }

    public function generateLeadNumber(): string
    {
        $prefix = config('truckfund.lead_number_prefix', 'LD');
        $seq = str_pad((string) ($this->query()->count() + 1), 6, '0', STR_PAD_LEFT);

        return $prefix.'-'.date('Ymd').'-'.$seq;
    }
}
