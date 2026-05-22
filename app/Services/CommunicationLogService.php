<?php

namespace App\Services;

use App\Enums\CommType;
use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Auth;

class CommunicationLogService
{
    public function logForLead(string $leadId, CommType $type, string $content, ?int $platform = null): CommunicationLog
    {
        return CommunicationLog::query()->create([
            'lead_id' => $leadId,
            'user_id' => Auth::id(),
            'type' => $type,
            'platform' => $platform,
            'content' => $content,
        ]);
    }

    public function logForApplication(string $appId, CommType $type, string $content, ?int $platform = null): CommunicationLog
    {
        return CommunicationLog::query()->create([
            'app_id' => $appId,
            'user_id' => Auth::id(),
            'type' => $type,
            'platform' => $platform,
            'content' => $content,
        ]);
    }
}
