<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceApplication extends BaseUuidModel
{
    protected $primaryKey = 'app_id';

    protected $fillable = [
        'app_number',
        'customer_id',
        'user_id',
        'source',
        'financial_merchant_id',
        'fin_merchant_agent_id',
        'automotive_status',
        'auto_product_id',
        'financial_product_id',
        'status',
        'booking_effective_date',
        'down_payment',
        'total_loan_amount',
        'total_truck_price',
        'monthly_income',
        'customer_comm_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'booking_effective_date' => 'date',
            'down_payment' => 'decimal:2',
            'total_loan_amount' => 'decimal:2',
            'total_truck_price' => 'decimal:2',
            'monthly_income' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'financial_merchant_id', 'merchant_id');
    }

    public function autoProduct(): BelongsTo
    {
        return $this->belongsTo(AutoProduct::class, 'auto_product_id', 'id');
    }

    public function financialProduct(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'financial_product_id', 'product_id');
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'app_id', 'app_id');
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'app_id', 'app_id');
    }
}

