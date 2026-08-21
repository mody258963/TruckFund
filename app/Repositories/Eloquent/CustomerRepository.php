<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findWithRelations(string $id): ?Customer
    {
        return $this->query()
            ->with([
                'lead',
                'identification',
                'references',
                'financialData',
                'documents',
                'agriculturalLands',
                'financeApplications',
            ])
            ->find($id);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->withExists([
                'financeApplications as has_reentry_due' => fn ($q) => $q
                    ->whereNotNull('reentry_due_at')
                    ->whereDate('reentry_due_at', '<=', now()->toDateString()),
            ])
            ->orderByDesc('has_reentry_due')
            ->latest()
            ->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $s = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q
                ->where('display_name', 'like', $s)
                ->orWhere('mobile_number', 'like', $s)
                ->orWhere('email', 'like', $s));
        }
        if (isset($filters['profile_completed'])) {
            $query->where('profile_completed', (bool) $filters['profile_completed']);
        }
        if (! empty($filters['reentry_due'])) {
            $query->whereHas('financeApplications', fn ($q) => $q
                ->whereNotNull('reentry_due_at')
                ->whereDate('reentry_due_at', '<=', now()->toDateString()));
        }

        return $query;
    }
}
