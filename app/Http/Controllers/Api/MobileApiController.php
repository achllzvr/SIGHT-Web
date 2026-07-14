<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileApiController extends Controller
{
    public function __construct(private readonly MetricsService $metricsService)
    {
    }

    /**
     * POST /api/mobile/child/login
     * Authenticates via login_code to a specific child profile
     */
    public function loginChild(Request $request)
    {
        $request->validate([
            'login_code' => 'required|string|size:6',
            'password' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        $result = $this->metricsService->loginChild(
            $request->string('login_code')->toString(),
            $request->input('password'),
            $request->input('device_id')
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/child/{child_id}/sync/metrics
     * Ingests SQLite logs from phone into cloud database
     */
    public function syncMetrics(Request $request, $child_id)
    {
        $request->validate([
            'metrics' => 'required|array|min:1',
            'metrics.*.avg_blink_rate' => 'nullable|numeric',
            'metrics.*.avg_distance' => 'nullable|numeric',
            'metrics.*.strain_events' => 'nullable|integer',
            'metrics.*.screen_time_minutes' => 'nullable|integer',
            'metrics.*.health_score' => 'nullable|integer',
            'metrics.*.coins' => 'nullable|integer',
            'metrics.*.timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $result = $this->metricsService->syncMetrics(
            (int) $child_id,
            (int) Auth::id(),
            $request->input('metrics', [])
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/child/{child_id}/sync/metrics/batch
     * Efficiently ingests batched 30-minute metric arrays with deduplication
     */
    public function ingestBatchMetrics(Request $request, $child_id)
    {
        $request->validate([
            'metrics' => 'required|array|min:1',
            'metrics.*.avg_blink_rate' => 'nullable|numeric',
            'metrics.*.avg_distance' => 'nullable|numeric',
            'metrics.*.strain_events' => 'nullable|integer',
            'metrics.*.screen_time_minutes' => 'nullable|integer',
            'metrics.*.health_score' => 'nullable|integer',
            'metrics.*.coins' => 'nullable|integer',
            'metrics.*.timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $result = $this->metricsService->ingestBatchMetrics(
            (int) $child_id,
            (int) Auth::id(),
            $request->input('metrics', [])
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * PUT /api/mobile/child/{child_id}/sync/pet
     * Backs up virtual pet progress with timestamp comparison
     */
    public function syncPet(Request $request, $child_id)
    {
        $request->validate([
            'xp_points' => 'required|integer|min:0',
            'currency' => 'required|integer|min:0',
            'current_streak_days' => 'nullable|integer|min:0',
            'last_streak_date' => 'nullable|date',
            'pet_state' => 'nullable|in:Healthy,Good,Critical,Dead',
            'device_timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $result = $this->metricsService->syncPet(
            (int) $child_id,
            (int) Auth::id(),
            $request->all()
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/child/{child_id}/sync/calibration
     * Uploads unique TFLite facial mesh baseline
     */
    public function syncCalibration(Request $request, $child_id)
    {
        $request->validate([
            'calibration_baseline' => 'required|json',
        ]);

        $result = $this->metricsService->syncCalibration(
            (int) $child_id,
            (int) Auth::id(),
            $request->input('calibration_baseline')
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/device/register-token
     * Saves FCM token for push notifications
     */
    public function registerFCMToken(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:child_profile,child_id',
            'fcm_token' => 'required|string',
        ]);

        $result = $this->metricsService->registerFcmToken(
            (int) $request->input('child_id'),
            (int) Auth::id(),
            $request->string('fcm_token')->toString()
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/device/ping
     * Lightweight heartbeat to update last_active
     */
    public function devicePing(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:child_profile,child_id',
        ]);

        $result = $this->metricsService->devicePing(
            (int) $request->input('child_id'),
            (int) Auth::id()
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * PUT /api/mobile/child/{child_id}/sync/limits
     * Delta sync of session limits with conflict resolution
     */
    public function syncSessionLimits(Request $request, $child_id)
    {
        $request->validate([
            'daily_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'mode' => 'nullable|in:Strict,Relaxed',
            'harmful_distance_threshold' => 'nullable|numeric|min:1|max:100',
            'critical_distance_threshold' => 'nullable|numeric|min:1|max:100',
            'auto_enforce_breaks' => 'nullable|boolean',
            'device_timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $result = $this->metricsService->syncSessionLimits(
            (int) $child_id,
            (int) Auth::id(),
            $request->all()
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * GET /api/mobile/config
     * Returns minimum app version requirement
     */
    public function getConfig()
    {
        $result = $this->metricsService->getConfig();

        return response()->json($result['body'], $result['http_code']);
    }
}
