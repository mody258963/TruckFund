<?php

namespace App\Models;

use App\Enums\DocType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'doc_id';

    protected $fillable = [
        'customer_id',
        'doc_type',
        'file_url',
        'uploaded_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'doc_type' => DocType::class,
            'uploaded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}

