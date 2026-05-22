<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('changedByUser');

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        return $query->orderByDesc('changed_at')->paginate($perPage);
    }
}
