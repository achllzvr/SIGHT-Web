<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\GuardianProfile;
use App\Models\PatientAccessLog;
use App\Models\TemporaryAccessToken;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientAccessSessionService
{
    public function __construct(
        private readonly TemporaryAccessTokenService $tokenService,
        private readonly ClinicalAnalyticsService $analytics,
        private readonly UserPresenceService $presence,
    ) {
    }

    /**
     * Redeem OTP/QR, open viewing session, return telemetry snapshot.
     */
    public function redeem(int $clinicianUserId, string $codeOrPayload): array
    {
        $clinician = User::find($clinicianUserId);
        if (!$clinician || strtolower((string) $clinician->role) !== 'doctor') {
            return $this->error('Unauthorized', 403, 'UNAUTHORIZED');
        }

        $token = $this->tokenService->findValidToken($codeOrPayload);
        if (!$token) {
            return $this->error('Invalid or expired access code', 422, 'INVALID_TOKEN');
        }

        try {
            $session = DB::transaction(function () use ($token, $clinicianUserId) {
                $locked = TemporaryAccessToken::where('id', $token->id)->lockForUpdate()->first();
                if (!$locked || !$locked->isValid()) {
                    throw new HttpException(422, 'Invalid or expired access code');
                }

                $this->endActiveSessionsForChild($locked->child_id, PatientAccessLog::ENDED_BY_SYSTEM);

                $this->tokenService->markUsed($locked);

                return PatientAccessLog::create([
                    'clinician_id' => $clinicianUserId,
                    'child_id' => $locked->child_id,
                    'temporary_access_token_id' => $locked->id,
                    'accessed_at' => now(),
                    'status' => PatientAccessLog::STATUS_ACTIVE,
                ]);
            });
        } catch (HttpException $e) {
            return $this->error($e->getMessage() ?: 'Invalid or expired access code', $e->getStatusCode(), 'INVALID_TOKEN');
        }

        $session->load(['child.user', 'child.guardians.user', 'clinician']);
        $telemetry = $this->buildTelemetry($session->child);

        return $this->ok('Session started', [
            'session_id' => $session->id,
            'status' => $session->status,
            'accessed_at' => $session->accessed_at->toIso8601String(),
            'child' => $this->childPayload($session->child),
            'clinician' => [
                'user_id' => $clinician->user_id,
                'name' => $clinician->display_name,
            ],
            'telemetry' => $telemetry,
        ]);
    }

    public function assertActive(int $sessionId, int $clinicianUserId): PatientAccessLog
    {
        $session = PatientAccessLog::with(['child.user', 'clinician'])->find($sessionId);

        if (!$session || (int) $session->clinician_id !== $clinicianUserId) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'Session not found',
                'code' => 'SESSION_NOT_FOUND',
            ], 404));
        }

        if (!$session->isActive()) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'Viewing session has ended',
                'code' => 'SESSION_ENDED',
            ], 403));
        }

        return $session;
    }

    public function getActiveForClinician(int $clinicianUserId): ?array
    {
        $session = PatientAccessLog::with(['child.user', 'clinician'])
            ->where('clinician_id', $clinicianUserId)
            ->where('status', PatientAccessLog::STATUS_ACTIVE)
            ->orderByDesc('accessed_at')
            ->first();

        if (!$session) {
            return null;
        }

        return $this->sessionPayload($session, true);
    }

    public function getActiveForChild(int $guardianUserId, int $childId): ?array
    {
        $this->assertGuardianOwnsChild($guardianUserId, $childId);

        $session = PatientAccessLog::with(['child.user', 'clinician'])
            ->where('child_id', $childId)
            ->where('status', PatientAccessLog::STATUS_ACTIVE)
            ->orderByDesc('accessed_at')
            ->first();

        if (!$session) {
            return null;
        }

        return $this->sessionPayload($session, false);
    }

    public function endByClinician(int $clinicianUserId, int $sessionId): array
    {
        $session = $this->assertActive($sessionId, $clinicianUserId);
        $this->endSession($session, PatientAccessLog::ENDED_BY_CLINICIAN);

        return $this->ok('Session ended', ['session_id' => $session->id, 'status' => PatientAccessLog::STATUS_ENDED]);
    }

    public function endByGuardian(int $guardianUserId, int $sessionId): array
    {
        $session = PatientAccessLog::with('child')->find($sessionId);
        if (!$session) {
            return $this->error('Session not found', 404, 'SESSION_NOT_FOUND');
        }

        $this->assertGuardianOwnsChild($guardianUserId, (int) $session->child_id);

        if (!$session->isActive()) {
            return $this->ok('Session already ended', [
                'session_id' => $session->id,
                'status' => $session->status,
            ]);
        }

        $this->endSession($session, PatientAccessLog::ENDED_BY_GUARDIAN);

        return $this->ok('Session ended', ['session_id' => $session->id, 'status' => PatientAccessLog::STATUS_ENDED]);
    }

    public function historyForClinician(int $clinicianUserId): array
    {
        $rows = PatientAccessLog::with(['child.user', 'clinician'])
            ->where('clinician_id', $clinicianUserId)
            ->orderByDesc('accessed_at')
            ->limit(100)
            ->get()
            ->map(fn (PatientAccessLog $log) => $this->historyRow($log))
            ->values()
            ->all();

        return $this->ok('Viewing history', ['logs' => $rows]);
    }

    public function historyForChild(int $guardianUserId, int $childId): array
    {
        $guardian = GuardianProfile::where('user_id', $guardianUserId)->first();
        if (!$guardian) {
            return $this->error('Guardian profile not found', 404, 'NOT_FOUND');
        }

        $owns = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();

        if (!$owns) {
            return $this->error('Unauthorized', 403, 'UNAUTHORIZED');
        }

        $rows = PatientAccessLog::with(['child.user', 'clinician'])
            ->where('child_id', $childId)
            ->orderByDesc('accessed_at')
            ->limit(100)
            ->get()
            ->map(fn (PatientAccessLog $log) => $this->historyRow($log))
            ->values()
            ->all();

        return $this->ok('Access history', ['logs' => $rows]);
    }

    /** All viewing sessions across every child linked to this guardian. */
    public function historyForGuardian(int $guardianUserId): array
    {
        $guardian = GuardianProfile::where('user_id', $guardianUserId)->first();
        if (!$guardian) {
            return $this->error('Guardian profile not found', 404, 'NOT_FOUND');
        }

        $childIds = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->pluck('child_id');

        if ($childIds->isEmpty()) {
            return $this->ok('Access history', ['logs' => []]);
        }

        $rows = PatientAccessLog::with(['child.user', 'clinician'])
            ->whereIn('child_id', $childIds)
            ->orderByDesc('accessed_at')
            ->limit(100)
            ->get()
            ->map(fn (PatientAccessLog $log) => $this->historyRow($log))
            ->values()
            ->all();

        return $this->ok('Access history', ['logs' => $rows]);
    }

    public function telemetryForSession(int $clinicianUserId, int $sessionId, string $range = '7d'): array
    {
        $session = $this->assertActive($sessionId, $clinicianUserId);
        $session->loadMissing('child.user');

        return $this->ok('Telemetry', [
            'session_id' => $session->id,
            'child' => $this->childPayload($session->child),
            'telemetry' => $this->buildTelemetry($session->child, $range),
        ]);
    }

    public function buildTelemetry(?ChildProfile $child, string $range = '7d'): array
    {
        if (!$child) {
            return $this->emptyTelemetry();
        }

        $dashboard = $this->analytics->getChildDashboardMetrics($child, $range);
        $series = $dashboard['series'] ?? [];
        $childUser = $child->user_id ? User::find($child->user_id) : null;
        $presence = $childUser ? $this->presence->summarize($childUser) : [
            'presence_status' => 'offline',
            'presence_label' => 'Offline',
            'last_active' => 'Never',
        ];

        $scores = $child->eyeHealthScores()->orderByDesc('recorded_date')->limit(14)->get();

        return [
            'harmful_distance_cm' => 30,
            'range' => $dashboard['range'],
            'range_label' => $dashboard['range_label'],
            'health_grade' => $dashboard['health_grade'],
            'health_score' => $dashboard['health_score'],
            'latest_coins' => 0,
            'average_blink_rate' => $dashboard['mean_blink_rate'],
            'average_distance' => $dashboard['mean_distance_cm'],
            'average_screen_time' => $dashboard['mean_screen_time_minutes'],
            'mean_diopters' => $dashboard['mean_diopters'] ?? null,
            'distance_violations' => (int) ($dashboard['distance_violations'] ?? 0),
            'low_blink_events' => (int) ($dashboard['low_blink_events'] ?? 0),
            'labels' => $series['labels'] ?? [],
            'blink_rates' => $series['blink_rates'] ?? [],
            'distances' => $series['distances'] ?? [],
            'screen_times' => $series['screen_times'] ?? [],
            'strain_events' => $series['strain_events'] ?? [],
            'health_scores' => $series['health_scores'] ?? [],
            'coins_data' => [],
            'has_data' => (bool) ($dashboard['has_data'] ?? false),
            'ocular_protection_index' => $dashboard['ocular_protection_index'] ?? null,
            'usage_threshold' => $dashboard['usage_threshold'] ?? null,
            'clinical_trend_alert' => $dashboard['clinical_trend_alert'] ?? null,
            'age_max_minutes' => $dashboard['age_max_minutes'] ?? 120,
            'presence' => $presence,
            'scores' => $scores->map(fn ($s) => [
                'daily_score' => $s->daily_score,
                'grade' => $s->grade,
                'recorded_date' => optional($s->recorded_date)->toDateString() ?? $s->recorded_date,
            ])->values()->all(),
            'raw_metrics_count' => $child->eyeHealthMetrics()->count(),
        ];
    }

    private function endActiveSessionsForChild(int $childId, string $endedBy): void
    {
        PatientAccessLog::where('child_id', $childId)
            ->where('status', PatientAccessLog::STATUS_ACTIVE)
            ->get()
            ->each(fn (PatientAccessLog $log) => $this->endSession($log, $endedBy));
    }

    private function endSession(PatientAccessLog $session, string $endedBy): void
    {
        $session->update([
            'status' => PatientAccessLog::STATUS_ENDED,
            'ended_at' => now(),
            'ended_by' => $endedBy,
        ]);
    }

    private function assertGuardianOwnsChild(int $guardianUserId, int $childId): void
    {
        $guardian = GuardianProfile::where('user_id', $guardianUserId)->first();
        if (!$guardian) {
            abort(404, 'Guardian profile not found');
        }

        $owns = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();

        if (!$owns) {
            abort(403, 'Unauthorized');
        }
    }

    private function sessionPayload(PatientAccessLog $session, bool $includeTelemetry): array
    {
        $payload = [
            'session_id' => $session->id,
            'status' => $session->status,
            'accessed_at' => optional($session->accessed_at)?->toIso8601String(),
            'ended_at' => optional($session->ended_at)?->toIso8601String(),
            'ended_by' => $session->ended_by,
            'child' => $this->childPayload($session->child),
            'clinician' => [
                'user_id' => $session->clinician_id,
                'name' => $session->clinician?->display_name ?? 'Clinician',
            ],
        ];

        if ($includeTelemetry) {
            $payload['telemetry'] = $this->buildTelemetry($session->child);
        }

        return $payload;
    }

    private function historyRow(PatientAccessLog $log): array
    {
        return [
            'session_id' => $log->id,
            'child_id' => $log->child_id,
            'child_name' => $log->child?->user?->display_name ?? 'Unknown',
            'clinician_id' => $log->clinician_id,
            'clinician_name' => $log->clinician?->display_name ?? 'Unknown',
            'accessed_at' => optional($log->accessed_at)?->toIso8601String(),
            'ended_at' => optional($log->ended_at)?->toIso8601String(),
            'status' => $log->status,
            'ended_by' => $log->ended_by,
            'token_id' => $log->temporary_access_token_id,
        ];
    }

    private function childPayload(?ChildProfile $child): array
    {
        if (!$child) {
            return [];
        }

        return [
            'child_id' => $child->child_id,
            'name' => $child->user?->display_name ?? 'Unknown',
            'birthdate' => $child->birthdate,
            'patient_code' => 'PT-2026-' . str_pad((string) $child->child_id, 3, '0', STR_PAD_LEFT),
            'initials' => $child->user?->initials ?? 'PT',
            'guardian' => $child->guardians->first()?->user?->display_name ?? null,
            'last_sync' => optional($child->last_sync)?->toIso8601String(),
        ];
    }

    private function emptyTelemetry(): array
    {
        return [
            'harmful_distance_cm' => 30,
            'health_grade' => 'No Data',
            'health_score' => null,
            'latest_coins' => 0,
            'average_blink_rate' => null,
            'average_distance' => null,
            'average_screen_time' => null,
            'distance_violations' => 0,
            'low_blink_events' => 0,
            'labels' => [],
            'blink_rates' => [],
            'distances' => [],
            'screen_times' => [],
            'strain_events' => [],
            'health_scores' => [],
            'coins_data' => [],
            'has_data' => false,
            'scores' => [],
            'raw_metrics_count' => 0,
        ];
    }

    private function avg(array $values): ?float
    {
        $filtered = array_values(array_filter($values, fn ($v) => $v !== null));
        if ($filtered === []) {
            return null;
        }

        return round(array_sum(array_map('floatval', $filtered)) / count($filtered), 1);
    }

    private function latest(array $values): ?float
    {
        $filtered = array_values(array_filter($values, fn ($v) => $v !== null));
        if ($filtered === []) {
            return null;
        }

        return round((float) end($filtered), 1);
    }

    private function gradeFromScore(?float $score): string
    {
        if ($score === null) {
            return 'No Data';
        }
        if ($score >= 90) {
            return 'Excellent';
        }
        if ($score >= 80) {
            return 'Good';
        }
        if ($score >= 70) {
            return 'Fair';
        }

        return 'Needs Attention';
    }

    private function ok(string $message, array $data = [], int $code = 200): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'success',
                'message' => $message,
                'data' => $data,
                'errors' => null,
            ],
        ];
    }

    private function error(string $message, int $code, string $errorCode): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'error',
                'message' => $message,
                'code' => $errorCode,
                'data' => new \stdClass(),
                'errors' => ['error' => [$message]],
            ],
        ];
    }
}
