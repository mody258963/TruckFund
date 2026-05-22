<?php

namespace App\Models;


class FinancialProduct extends BaseUuidModel
{
    protected $primaryKey = 'product_id';

    protected $fillable = ['name', 'product_code', 'percentage', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

