<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends BaseUuidModel
{
    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'lead_id',
        'display_name',
        'mobile_number',
        'email',
        'source',
        'nationality',
        'date_of_birth',
        'gender',
        'marital_status',
        'job_status',
        'occupation',
        'organization_name',
        'city',
        'area',
        'address',
        'onboarding_step',
        'profile_completed',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'profile_completed' => 'boolean',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'lead_id');
    }

    public function identification(): HasOne
    {
        return $this->hasOne(Identification::class, 'customer_id', 'customer_id');
    }

    public function agriculturalLands(): HasMany
    {
        return $this->hasMany(AgriculturalLand::class, 'customer_id', 'customer_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class, 'customer_id', 'customer_id');
    }

    public function financialData(): HasOne
    {
        return $this->hasOne(FinancialData::class, 'customer_id', 'customer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'customer_id', 'customer_id');
    }

    public function financeApplications(): HasMany
    {
        return $this->hasMany(FinanceApplication::class, 'customer_id', 'customer_id');
    }
}

