<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialData extends BaseUuidModel
{
    public $timestamps = false;

    protected $primaryKey = 'fin_id';

    protected $table = 'financial_data';

    protected $fillable = [
        'customer_id',
        'has_income_proof',
        'annual_sales_1yr',
        'annual_sales_2yr',
        'org_name',
        'commercial_reg_type',
        'commercial_reg_num',
        'reg_start_date',
        'reg_expiry_date',
        'paid_in_capital',
        'org_city',
        'org_address',
    ];

    protected function casts(): array
    {
        return [
            'has_income_proof' => 'boolean',
            'reg_start_date' => 'date',
            'reg_expiry_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}

