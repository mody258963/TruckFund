<?php

namespace App\Models;

use App\Enums\AutoProductType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AutoProduct extends BaseUuidModel
{
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'type', 'brand', 'model', 'model_year', 'chassis', 'price', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => AutoProductType::class,
            'model_year' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function financeApplications(): BelongsToMany
    {
        return $this->belongsToMany(
            FinanceApplication::class,
            'finance_application_auto_product',
            'auto_product_id',
            'app_id',
            'id',
            'app_id',
        )->withPivot('sort_order')->withTimestamps();
    }

    public function label(): string
    {
        return collect([
            $this->brand,
            $this->name,
            $this->model,
            $this->model_year,
        ])->filter(fn ($part) => filled($part))->implode(' — ');
    }
}
