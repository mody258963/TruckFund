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

        return $query;
    }
}
