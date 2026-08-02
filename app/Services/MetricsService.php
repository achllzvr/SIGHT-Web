<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\EyeHealthMetrics;
use App\Models\SessionLimits;
use App\Models\User;
use App\Models\VirtualPet;
use Illuminate\Support\Carbon;

class MetricsService
{
    public function loginChild(string $loginCode, string $password, ?string $deviceId): array
    {
        $child = ChildProfile::with('guardians.user')->where('login_code', $loginCode)->first();

        if (!$child) {
            return $this->response('error', 'Invalid login code', null, ['login_code' => ['Invalid login code']], 401);
        }

        $childUser = User::find($child->user_id);

        // NEW: Verify Password
        if (!$childUser || !\Illuminate\Support\Facades\Hash::check($password, $childUser->password_hash)) {
            return $this->response('error', 'Invalid password', null, ['password' => ['Invalid password']], 401);
        }

        if ($deviceId) {
            $child->update(['device_id' => $deviceId]);
        }

        // Extract guardian email for Flutter caching
        $guardianEmail = $child->guardians->first()?->user?->email ?? '';
        $childUser->tokens()->where('name', 'child-mobile')->delete(); 
        $token = $childUser->createToken('child-mobile')->plainTextToken;

        return $this->response('success', 'Child login successful', [
            'display_name' => $childUser->display_name ?? 'Child',
            'child_id' => $child->child_id,
            'guardian_email' => $guardianEmail,
            'access_token' => $token,
        ]);
    }

    public function syncMetrics(int $childId, int $authUserId, array $metrics): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }
        $isChild = (int) $child->user_id === (int) $authUserId;
        
        $isLinkedGuardian = \Illuminate\Support\Facades\DB::table('guardian_child_link')
            ->join('guardian_profile', 'guardian_child_link.guardian_id', '=', 'guardian_profile.guardian_id')
            ->where('guardian_child_link.child_id', $childId)
            ->where('guardian_profile.user_id', $authUserId)
            ->exists();

        if (!$isChild && !$isLinkedGuardian) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        if (empty($metrics)) {
            return $this->response('error', 'Metrics is empty', null, ['metrics' => ['Metrics is empty']], 400);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $inserted = 0;

        foreach ($metrics as $metric) {
            EyeHealthMetrics::create([
                'child_id' => $childId,
                'avg_blink_rate' => $metric['avg_blink_rate'] ?? null,
                'avg_distance' => $metric['avg_distance'] ?? null,
                'strain_events' => $metric['strain_events'] ?? null,
                'screen_time_minutes' => $metric['screen_time_minutes'] ?? 0,
                'health_score'        => $metric['health_score'] ?? null,
                'coins'                => $metric['coins'] ?? null,
                'timestamp' => $metric['timestamp'],
            ]);
            $inserted++;
        }

        $child->update(['last_sync' => now()]);

        return $this->response('success', 'Metrics synced successfully', [
            'inserted_records' => $inserted,
        ]);
    }

    public function ingestBatchMetrics(int $childId, int $authUserId, array $metricsBatch): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        $isChild = (int) $child->user_id === (int) $authUserId;
        
        $isLinkedGuardian = \Illuminate\Support\Facades\DB::table('guardian_child_link')
            ->join('guardian_profile', 'guardian_child_link.guardian_id', '=', 'guardian_profile.guardian_id')
            ->where('guardian_child_link.child_id', $childId)
            ->where('guardian_profile.user_id', $authUserId)
            ->exists();

        if (!$isChild && !$isLinkedGuardian) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        if (empty($metricsBatch)) {
            return $this->response('error', 'Metrics batch is empty', null, ['metrics' => ['Metrics batch is empty']], 400);
        }

        $inserted = 0;
        $skipped = 0;
        $records = [];

        // Extract all timestamps to check for existing records
        $timestamps = array_column($metricsBatch, 'timestamp');
        $minTimestamp = min($timestamps);
        $maxTimestamp = max($timestamps);

        // Find existing metrics in the timestamp range for this child
        $existingMetrics = EyeHealthMetrics::where('child_id', $childId)
            ->whereBetween('timestamp', [$minTimestamp, $maxTimestamp])
            ->pluck('timestamp')
            ->map(fn ($ts) => $ts->toDateTimeString())
            ->toArray();

        // Process each metric, deduplicating by timestamp (DB + within this request)
        $seenInBatch = [];
        foreach ($metricsBatch as $metric) {
            $timestamp = $metric['timestamp'];

            // Skip if this exact timestamp already exists online or earlier in this payload
            if (isset($seenInBatch[$timestamp]) || in_array($timestamp, $existingMetrics, true)) {
                $skipped++;
                continue;
            }
            $seenInBatch[$timestamp] = true;

            $records[] = [
                'child_id' => $childId,
                'avg_blink_rate' => $metric['avg_blink_rate'] ?? null,
                'avg_distance' => $metric['avg_distance'] ?? null,
                'strain_events' => $metric['strain_events'] ?? null,
                'screen_time_minutes' => $metric['screen_time_minutes'] ?? 0,
                'health_score'        => $metric['health_score'] ?? null,
                'coins'                => $metric['coins'] ?? null,
                'timestamp' => $timestamp,
            ];
        }

        // Bulk insert all new records
        if (!empty($records)) {
            EyeHealthMetrics::insert($records);
            $inserted = count($records);
        }

        $child->update(['last_sync' => now()]);

        return $this->response('success', 'Batch metrics ingested successfully', [
            'inserted_records' => $inserted,
            'skipped_duplicates' => $skipped,
            'total_processed' => count($metricsBatch),
        ]);
    }

    public function syncPet(int $childId, int $authUserId, array $payload): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $pet = VirtualPet::where('child_id', $childId)->first();

        if (!$pet) {
            return $this->response('error', 'Pet not found', null, ['pet' => ['Pet not found']], 404);
        }

        $deviceTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $payload['device_timestamp'])->getTimestamp();
        $cloudTimestamp = $pet->updated_at ? Carbon::parse($pet->updated_at)->getTimestamp() : 0;

        if ($deviceTimestamp >= $cloudTimestamp) {
            $pet->update([
                'xp_points' => $payload['xp_points'],
                'currency' => $payload['currency'],
                'current_streak_days' => $payload['current_streak_days'] ?? $pet->current_streak_days,
                'last_streak_date' => $payload['last_streak_date'] ?? $pet->last_streak_date,
                'pet_state' => $payload['pet_state'] ?? $pet->pet_state,
                'updated_at' => now(),
            ]);

            return $this->response('success', 'Pet synced successfully', [
                'synced' => true,
                'pet' => [
                    'xp_points' => $pet->xp_points,
                    'currency' => $pet->currency,
                    'current_streak_days' => $pet->current_streak_days,
                    'pet_state' => $pet->pet_state,
                    'updated_at' => optional($pet->updated_at)->toIso8601String(),
                ],
            ]);
        }

        return $this->response('success', 'Cloud data is newer, not updating', [
            'synced' => false,
            'pet' => [
                'xp_points' => $pet->xp_points,
                'currency' => $pet->currency,
                'current_streak_days' => $pet->current_streak_days,
                'pet_state' => $pet->pet_state,
                'updated_at' => optional($pet->updated_at)->toIso8601String(),
            ],
        ]);
    }

    public function syncCalibration(int $childId, int $authUserId, string $calibrationBaseline): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $child->update([
            'calibration_baseline' => $calibrationBaseline,
        ]);

        return $this->response('success', 'Calibration baseline synced successfully');
    }

    public function syncSessionLimits(int $childId, int $authUserId, array $payload): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $limits = SessionLimits::where('child_id', $childId)->first();

        if (!$limits) {
            return $this->response('error', 'Session limits not found', null, ['session_limits' => ['Session limits not found']], 404);
        }

        // Delta sync: compare device timestamp with server timestamp
        $deviceTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $payload['device_timestamp'])->getTimestamp();
        $cloudTimestamp = $limits->updated_at ? Carbon::parse($limits->updated_at)->getTimestamp() : 0;

        // If device data is newer or equal, accept the update
        if ($deviceTimestamp >= $cloudTimestamp) {
            $limits->update([
                'daily_limit_minutes' => $payload['daily_limit_minutes'] ?? $limits->daily_limit_minutes,
                'mode' => $payload['mode'] ?? $limits->mode,
                'harmful_distance_threshold' => $payload['harmful_distance_threshold'] ?? $limits->harmful_distance_threshold,
                'critical_distance_threshold' => $payload['critical_distance_threshold'] ?? $limits->critical_distance_threshold,
                'auto_enforce_breaks' => $payload['auto_enforce_breaks'] ?? $limits->auto_enforce_breaks,
                'updated_at' => now(),
            ]);

            return $this->response('success', 'Session limits synced successfully', [
                'synced' => true,
                'limits' => [
                    'daily_limit_minutes' => $limits->daily_limit_minutes,
                    'mode' => $limits->mode,
                    'harmful_distance_threshold' => $limits->harmful_distance_threshold,
                    'critical_distance_threshold' => $limits->critical_distance_threshold,
                    'auto_enforce_breaks' => $limits->auto_enforce_breaks,
                    'updated_at' => optional($limits->updated_at)->toIso8601String(),
                ],
            ]);
        }

        // Server data is newer: reject mobile update and return current server state
        return $this->response('success', 'Server data is newer, mobile update rejected', [
            'synced' => false,
            'limits' => [
                'daily_limit_minutes' => $limits->daily_limit_minutes,
                'mode' => $limits->mode,
                'harmful_distance_threshold' => $limits->harmful_distance_threshold,
                'critical_distance_threshold' => $limits->critical_distance_threshold,
                'auto_enforce_breaks' => $limits->auto_enforce_breaks,
                'updated_at' => optional($limits->updated_at)->toIso8601String(),
            ],
        ]);
    }

    public function registerFcmToken(int $childId, int $authUserId, string $fcmToken): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $child->update(['fcm_token' => $fcmToken]);

        return $this->response('success', 'FCM token registered successfully');
    }

    public function devicePing(int $childId, int $authUserId): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        if ((int) $child->user_id !== (int) $authUserId) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $child->update(['last_sync' => now()]);

        return $this->response('success', 'Ping received', [
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function getConfig(): array
    {
        return $this->response('success', 'Config fetched successfully', [
            'minimum_version' => '1.0.0',
            'latest_version' => '1.0.5',
            'force_update' => false,
            'api_version' => '1.0',
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function response(string $status, string $message, ?array $data = [], $errors = null, int $httpCode = 200): array
    {
        return [
            'http_code' => $httpCode,
            'body' => [
                'status' => $status,
                'message' => $message,
                'data' => $data ?? new \stdClass(),
                'errors' => $errors,
            ],
        ];
    }
}
