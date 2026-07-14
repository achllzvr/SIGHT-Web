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
        $this->assertGuardianOwnsChild($guardianUserId, $childId);

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

    public function telemetryForSession(int $clinicianUserId, int $sessionId): array
    {
        $session = $this->assertActive($sessionId, $clinicianUserId);
        $session->loadMissing('child.user');

        return $this->ok('Telemetry', [
            'session_id' => $session->id,
            'child' => $this->childPayload($session->child),
            'telemetry' => $this->buildTelemetry($session->child),
        ]);
    }

    public function buildTelemetry(?ChildProfile $child): array
    {
        if (!$child) {
            return $this->emptyTelemetry();
        }

        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $metricRows = $child->eyeHealthMetrics()
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->selectRaw('DATE(`timestamp`) as metric_date, AVG(avg_blink_rate) as avg_blink_rate, AVG(avg_distance) as avg_distance, AVG(screen_time_minutes) as screen_time_minutes, SUM(strain_events) as strain_events, AVG(health_score) as health_score, MAX(coins) as coins')
            ->groupByRaw('DATE(`timestamp`)')
            ->orderBy('metric_date')
            ->get()
            ->keyBy('metric_date');

        $labels = [];
        $blinkRates = [];
        $distances = [];
        $screenTimes = [];
        $strainEvents = [];
        $healthScores = [];
        $coinsData = [];

        foreach (CarbonPeriod::create($startDate->toDateString(), $endDate->toDateString()) as $date) {
            $key = $date->format('Y-m-d');
            $metric = $metricRows->get($key);
            $labels[] = $date->format('D');
            $blinkRates[] = $metric ? round((float) $metric->avg_blink_rate, 1) : null;
            $distances[] = $metric ? round((float) $metric->avg_distance, 1) : null;
            $screenTimes[] = $metric ? round((float) $metric->screen_time_minutes, 1) : null;
            $strainEvents[] = $metric ? (int) $metric->strain_events : null;
            $healthScores[] = $metric && $metric->health_score !== null ? round((float) $metric->health_score, 1) : null;
            $coinsData[] = $metric && $metric->coins !== null ? (int) $metric->coins : null;
        }

        $metricValues = $metricRows->values();
        $avgDistance = $this->avg($metricValues->pluck('avg_distance')->all());
        $avgBlink = $this->avg($metricValues->pluck('avg_blink_rate')->all());
        $avgScreen = $this->avg($metricValues->pluck('screen_time_minutes')->all());
        $avgHealth = $this->avg($metricValues->pluck('health_score')->all());
        $latestHealth = $this->latest($metricValues->pluck('health_score')->all());
        $latestCoins = $this->latest($metricValues->pluck('coins')->all());

        $scores = $child->eyeHealthScores()->orderByDesc('recorded_date')->limit(14)->get();

        return [
            'harmful_distance_cm' => 30,
            'health_grade' => $this->gradeFromScore($latestHealth ?? $avgHealth),
            'health_score' => $latestHealth ?? $avgHealth,
            'latest_coins' => $latestCoins ?? 0,
            'average_blink_rate' => $avgBlink,
            'average_distance' => $avgDistance,
            'average_screen_time' => $avgScreen,
            'distance_violations' => $metricValues->filter(fn ($row) => (float) $row->avg_distance < 30)->count(),
            'low_blink_events' => $metricValues->filter(fn ($row) => (float) $row->avg_blink_rate < 12)->count(),
            'labels' => $labels,
            'blink_rates' => $blinkRates,
            'distances' => $distances,
            'screen_times' => $screenTimes,
            'strain_events' => $strainEvents,
            'health_scores' => $healthScores,
            'coins_data' => $coinsData,
            'has_data' => $metricValues->isNotEmpty(),
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
