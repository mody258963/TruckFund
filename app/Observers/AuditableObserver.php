<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function __construct(protected AuditService $auditService) {}

    public function updated(Model $model): void
    {
        if ($model->wasChanged()) {
            $this->auditService->log(
                $model,
                $model->getOriginal(),
                $model->getChanges()
            );
        }
    }

    public function created(Model $model): void
    {
        $this->auditService->log($model, [], $model->getAttributes());
    }
}
