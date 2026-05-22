<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseUuidModel
{
    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'governorate',
        'truck_type',
        'city',
        'comment',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(SupplierInventory::class, 'supplier_id', 'supplier_id');
    }
}

