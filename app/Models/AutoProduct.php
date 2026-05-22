<?php

namespace App\Models;


class AutoProduct extends BaseUuidModel
{
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'type', 'brand', 'chassis', 'price', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

