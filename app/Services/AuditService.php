<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action on a model.
     */
    public static function log(
        string $action,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '0.0.0.0',
            'user_agent' => Request::userAgent() ?? '',
            'created_at' => now(),
        ]);
    }

    /**
     * Log a model creation.
     */
    public static function logCreated(Model $model, ?string $action = null): AuditLog
    {
        return static::log(
            $action ?? 'created',
            $model,
            null,
            $model->toArray(),
        );
    }

    /**
     * Log a model update, capturing old and new values of changed attributes.
     */
    public static function logUpdated(Model $model, array $originalValues, ?string $action = null): AuditLog
    {
        $changed = $model->getChanges();

        // Filter original values to only include changed keys
        $old = array_intersect_key($originalValues, $changed);

        return static::log(
            $action ?? 'updated',
            $model,
            $old,
            $changed,
        );
    }

    /**
     * Log a custom action with optional metadata.
     */
    public static function logAction(string $action, Model $model, ?array $metadata = null): AuditLog
    {
        return static::log($action, $model, null, $metadata);
    }
}
