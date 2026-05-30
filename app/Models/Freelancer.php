<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Freelancer extends BaseUuidModel
{
    protected $primaryKey = 'freelancer_id';

    protected $fillable = [
        'full_name',
        'phone',
        'national_id',
        'id_card_url',
        'documents',
        'is_locked',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'is_locked' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'freelancer_id', 'freelancer_id');
    }
}
