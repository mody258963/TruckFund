<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Models\FinanceApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FinanceApplicationRepository extends EloquentRepository implements FinanceApplicationRepositoryInterface
{
    public function __construct(FinanceApplication $model)
    {
        parent::__construct($model);
    }

    public function findWithRelations(string $id): ?FinanceApplication
    {
        return $this->query()
            ->with([
                'customer.lead',
                'customer.identification',
                'customer.references',
                'customer.financialData',
                'customer.documents',
                'user',
                'merchant',
                'autoProduct',
                'financialProduct',
                'applicationDocuments',
                'communicationLogs',
            ])
            ->find($id);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $s = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q
                ->where('app_number', 'like', $s)
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('display_name', 'like', $s)
                    ->orWhere('mobile_number', 'like', $s)));
        }

        return $query;
    }

    public function generateAppNumber(): string
    {
        $prefix = config('truckfund.app_number_prefix', 'FA');
        $datedPrefix = $prefix.'-'.date('Ymd').'-';
        $lastNumber = $this->query()
            ->where('app_number', 'like', $datedPrefix.'%')
            ->max('app_number');
        $lastSequence = $lastNumber
            ? (int) substr($lastNumber, strlen($datedPrefix))
            : 0;
        $seq = str_pad((string) ($lastSequence + 1), 6, '0', STR_PAD_LEFT);

        return $datedPrefix.$seq;
    }

    public function countByStatus(ApplicationStatus $status): int
    {
        return $this->query()->where('status', $status)->count();
    }
}
