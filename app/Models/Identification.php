<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Identification extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'id_doc_id';

    protected $fillable = [
        'customer_id',
        'id_type',
        'id_number',
        'name_en',
        'name_ar',
        'issue_date',
        'expiry_date',
        'id_front_url',
        'id_back_url',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}

