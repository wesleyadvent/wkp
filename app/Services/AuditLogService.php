<?php

namespace App\Services;

use App\Models\LogAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(string $action, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null): void
    {
        LogAudit::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'table_name' => $model?->getTable(),
            'record_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
