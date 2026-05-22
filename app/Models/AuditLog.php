<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'audit_id';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'changed_by',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'user_id');
    }
}

