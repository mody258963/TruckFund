<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Models\FinanceApplication;
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
                'autoProducts',
                'financialProduct',
                'applicationDocuments',
                'communicationLogs',
                'funderReviewer',
            ])
            ->find($id);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (array_key_exists('funder_status', $filters) && $filters['funder_status'] !== '' && $filters['funder_status'] !== null) {
            if ($filters['funder_status'] === 'none') {
                $query->whereNull('funder_status');
            } else {
                $query->where('funder_status', $filters['funder_status']);
            }
        }
        if (! empty($filters['reentry_due'])) {
            $query->whereNotNull('reentry_due_at')
                ->whereDate('reentry_due_at', '<=', now()->toDateString());
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

    public function paginate(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->applyFilters(
            $this->query()->with(['customer', 'funderReviewer']),
            $filters,
        );

        return $query
            ->orderByRaw('CASE WHEN funder_status = ? THEN 0 WHEN funder_status IS NULL THEN 1 ELSE 2 END', [
                \App\Enums\FunderReviewStatus::NeedsAction->value,
            ])
            ->orderByRaw('CASE WHEN reentry_due_at IS NOT NULL AND reentry_due_at <= ? THEN 0 ELSE 1 END', [
                now()->toDateString(),
            ])
            ->latest()
            ->paginate($perPage);
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
