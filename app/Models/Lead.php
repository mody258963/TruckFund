<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends BaseUuidModel
{
    protected $primaryKey = 'lead_id';

    protected $fillable = [
        'lead_number',
        'customer_name',
        'phone',
        'email',
        'car_brand',
        'car_model',
        'manufacture_year',
        'price',
        'down_payment_pct',
        'value',
        'ai_score',
        'is_priority',
        'assigned_user_id',
        'created_by_user_id',
        'customer_id',
        'status',
        'source',
        'freelancer_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'value' => LeadValue::class,
            'source' => LeadSource::class,
            'is_priority' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id', 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class, 'freelancer_id', 'freelancer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'lead_id', 'lead_id');
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(LeadTransferRequest::class, 'lead_id', 'lead_id');
    }
}
