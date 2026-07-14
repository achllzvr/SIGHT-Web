<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClinicianTelemetryReportService;
use App\Services\PatientAccessSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Legacy web API doctor routes — now session-token based.
 */
class DoctorApiController extends Controller
{
    public function __construct(
        private readonly PatientAccessSessionService $sessionService,
        private readonly ClinicianTelemetryReportService $reportService,
    ) {
    }

    public function redeem(Request $request)
    {
        $validated = $request->validate(['code' => 'required|string|max:255']);
        $result = $this->sessionService->redeem((int) Auth::id(), $validated['code']);

        return response()->json($result['body'], $result['http_code']);
    }

    public function getAnalytics($child_id)
    {
        $active = $this->sessionService->getActiveForClinician((int) Auth::id());
        if (!$active || (int) ($active['child']['child_id'] ?? 0) !== (int) $child_id) {
            return response()->json(['message' => 'Unauthorized', 'code' => 'SESSION_ENDED'], 403);
        }

        $t = $active['telemetry'] ?? [];

        return response()->json([
            'child_id' => (int) $child_id,
            'analytics' => [
                'avg_blink_rate' => $t['average_blink_rate'] ?? 0,
                'avg_distance' => $t['average_distance'] ?? 0,
                'total_strain_events' => array_sum(array_filter($t['strain_events'] ?? [], fn ($v) => $v !== null)),
                'total_screen_time_minutes' => $t['average_screen_time'] ?? 0,
                'metrics_count' => $t['raw_metrics_count'] ?? 0,
                'distance_violations' => $t['distance_violations'] ?? 0,
            ],
        ]);
    }

    public function generateReport($child_id)
    {
        $active = $this->sessionService->getActiveForClinician((int) Auth::id());
        if (!$active || (int) ($active['child']['child_id'] ?? 0) !== (int) $child_id) {
            return response()->json(['message' => 'Unauthorized', 'code' => 'SESSION_ENDED'], 403);
        }

        return $this->reportService->downloadPdf((int) Auth::id(), (int) $active['session_id']);
    }
}
