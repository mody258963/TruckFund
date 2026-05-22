<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface CatalogRepositoryInterface
{
    public function paginate(string $modelClass, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function find(string $modelClass, string $id): ?Model;

    public function create(string $modelClass, array $data): Model;

    public function update(Model $model, array $data): Model;
}
