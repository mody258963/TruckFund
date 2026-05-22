<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function find(string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->latest()
            ->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query;
    }
}
