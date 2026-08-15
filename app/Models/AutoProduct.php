<?php

namespace App\Models;

use App\Enums\AutoProductType;

class AutoProduct extends BaseUuidModel
{
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'type', 'brand', 'model_year', 'chassis', 'price', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => AutoProductType::class,
            'model_year' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
