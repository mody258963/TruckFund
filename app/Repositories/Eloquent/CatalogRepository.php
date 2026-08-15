<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CatalogRepositoryInterface;
use App\Models\AutoProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatalogRepository implements CatalogRepositoryInterface
{
    public function paginate(string $modelClass, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $modelClass::query();

        if (! empty($filters['search'])) {
            $s = '%'.$filters['search'].'%';
            $query->where(function (Builder $builder) use ($modelClass, $s) {
                $builder->where('name', 'like', $s);

                if ($modelClass === AutoProduct::class) {
                    $builder->orWhere('brand', 'like', $s)
                        ->orWhere('chassis', 'like', $s)
                        ->orWhere('model_year', 'like', $s);
                }
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(string $modelClass, string $id): ?Model
    {
        return $modelClass::query()->find($id);
    }

    public function create(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }
}
