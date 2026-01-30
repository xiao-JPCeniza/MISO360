<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(Request $request, string $action, ?Model $target = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass() ?? 'system',
            'target_id' => $target?->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
