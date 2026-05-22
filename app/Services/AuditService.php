<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(Model $model, array $old, array $new): void
    {
        AuditLog::query()->create([
            'entity_type' => $model->getMorphClass(),
            'entity_id' => $model->getKey(),
            'changed_by' => Auth::id(),
            'old_value' => $old ?: null,
            'new_value' => $new ?: null,
        ]);
    }
}
