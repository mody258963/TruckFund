<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgriculturalLand extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'land_id';

    protected $table = 'agricultural_lands';

    protected $fillable = [
        'customer_id',
        'land_area_acres',
        'location',
        'governorate',
        'ownership_type',
        'contract_url',
        'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}

