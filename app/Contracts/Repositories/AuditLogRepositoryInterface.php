<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;
}
