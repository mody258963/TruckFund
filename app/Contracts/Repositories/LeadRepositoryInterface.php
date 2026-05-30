<?php

namespace App\Contracts\Repositories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeadRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = [], ?User $viewer = null): LengthAwarePaginator;

    public function findWithRelations(string $id): ?Lead;

    public function generateLeadNumber(): string;
}
