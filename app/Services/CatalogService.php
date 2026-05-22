<?php

namespace App\Services;

use App\Contracts\Repositories\CatalogRepositoryInterface;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Merchant;
use App\Models\Supplier;
use App\Models\SupplierInventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CatalogService
{
    public function __construct(protected CatalogRepositoryInterface $catalog) {}

    public function paginate(string $modelClass, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->catalog->paginate($modelClass, $perPage, $filters);
    }

    public function paginateMerchants(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(Merchant::class, 15, $filters);
    }

    public function paginateFinancialProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(FinancialProduct::class, 15, $filters);
    }

    public function paginateAutoProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(AutoProduct::class, 15, $filters);
    }

    public function paginateSuppliers(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(Supplier::class, 15, $filters);
    }

    public function paginateSupplierInventories(array $filters = [])
    {
        return SupplierInventory::query()->with('supplier')->latest()->paginate(15);
    }

    public function save(string $modelClass, ?string $id, array $data): Model
    {
        /** @var Model $instance */
        $instance = new $modelClass;
        $payload = collect($data)->only($instance->getFillable())->all();

        if ($id) {
            $model = $this->catalog->find($modelClass, $id);

            return $this->catalog->update($model, $payload);
        }

        return $this->catalog->create($modelClass, $payload);
    }
}
