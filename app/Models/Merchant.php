<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends BaseUuidModel
{
    protected $primaryKey = 'merchant_id';

    protected $fillable = ['name', 'type', 'contact_email', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function financeApplications(): HasMany
    {
        return $this->hasMany(FinanceApplication::class, 'financial_merchant_id', 'merchant_id');
    }
}

