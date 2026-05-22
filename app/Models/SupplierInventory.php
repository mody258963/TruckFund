<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInventory extends BaseUuidModel
{
    protected $primaryKey = 'supplier_product_id';

    protected $table = 'supplier_inventories';

    protected $fillable = [
        'supplier_id',
        'brand',
        'model',
        'type',
        'chassis',
        'kilometers',
        'condition',
        'price',
        'manufacture_year',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'kilometers' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}

