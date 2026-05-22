<?php

namespace App\Models;

use App\Enums\CommType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'comm_id';

    protected $fillable = [
        'lead_id',
        'app_id',
        'user_id',
        'type',
        'platform',
        'content',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CommType::class,
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'lead_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinanceApplication::class, 'app_id', 'app_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

