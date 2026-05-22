<?php

namespace App\Contracts\Repositories;

use App\Models\FinanceApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FinanceApplicationRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function findWithRelations(string $id): ?FinanceApplication;

    public function generateAppNumber(): string;
}
