<?php

namespace App\Models;

use App\Enums\DocType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'app_doc_id';

    protected $fillable = [
        'app_id',
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

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinanceApplication::class, 'app_id', 'app_id');
    }
}

