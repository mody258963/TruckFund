<?php

namespace App\Models;

use App\Enums\TransferRequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTransferRequest extends BaseUuidModel
{
    protected $primaryKey = 'transfer_id';

    protected $fillable = [
        'lead_id',
        'requested_by_user_id',
        'from_user_id',
        'to_user_id',
        'reviewed_by_user_id',
        'status',
        'reason',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'lead_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id', 'user_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id', 'user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id', 'user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }
}
