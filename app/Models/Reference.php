<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'ref_id';

    protected $fillable = [
        'customer_id',
        'full_name',
        'mobile',
        'relation',
        'same_address',
    ];

    protected function casts(): array
    {
        return ['same_address' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}

