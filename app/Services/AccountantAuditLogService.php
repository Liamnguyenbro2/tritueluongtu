<?php

namespace App\Services;

use App\Models\AccountantAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountantAuditLogService
{
    public function record(User $actor, string $action, string $description, ?Model $target = null, ?string $notes = null): AccountantAuditLog
    {
        return AccountantAuditLog::query()->create([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'description' => $description,
            'notes' => $notes,
        ]);
    }
}
