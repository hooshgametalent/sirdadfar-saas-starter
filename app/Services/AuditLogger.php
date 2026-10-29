<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, array $properties = [], ?User $actor = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $actor?->getKey() ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
