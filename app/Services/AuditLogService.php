<?php

namespace App\Services;

use App\Models\AdminProfile;
use App\Models\AuditLog;

class AuditLogService
{
    public function logAdminAction(?int $adminProfileId, string $action, string $targetEntity, ?string $ip = null, ?array $metadata = null, ?int $actorUserId = null): AuditLog
    {
        return AuditLog::create([
            'admin_id' => $adminProfileId,
            'actor_user_id' => $actorUserId,
            'action_taken' => $action,
            'target_entity' => $targetEntity,
            'ip_address' => $ip,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Ingest mobile activity payload into audit_logs.
     *
     * @param  array{event_tag:string,child_id?:int|null,old_value?:mixed,new_value?:mixed,occurred_at?:string|null}  $payload
     */
    public function ingestMobileActivity(int $actorUserId, array $payload, ?string $ip = null): array
    {
        $eventTag = (string) ($payload['event_tag'] ?? 'unknown.event');
        $childId = $payload['child_id'] ?? null;

        $log = AuditLog::create([
            'admin_id' => null,
            'actor_user_id' => $actorUserId,
            'action_taken' => $eventTag,
            'target_entity' => $childId !== null ? 'child:' . $childId : 'user:' . $actorUserId,
            'ip_address' => $ip,
            'metadata' => [
                'event_tag' => $eventTag,
                'child_id' => $childId,
                'old_value' => $payload['old_value'] ?? null,
                'new_value' => $payload['new_value'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now()->toIso8601String(),
                'source' => 'mobile',
            ],
        ]);

        return [
            'http_code' => 201,
            'body' => [
                'status' => 'success',
                'message' => 'Activity logged',
                'data' => [
                    'log_id' => $log->log_id,
                ],
                'errors' => null,
            ],
        ];
    }

    public function resolveAdminProfileId(int $userId): ?int
    {
        return AdminProfile::where('user_id', $userId)->value('admin_id');
    }
}
