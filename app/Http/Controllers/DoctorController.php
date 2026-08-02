<?php

namespace App\Http\Controllers;

use App\Services\ClinicianTelemetryReportService;
use App\Services\PatientAccessSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    public function __construct(
        private readonly PatientAccessSessionService $sessionService,
        private readonly ClinicianTelemetryReportService $reportService,
    ) {
    }

    public function dashboard(Request $request)
    {
        $doctor = Auth::user();
        if (is_null($doctor->email_verified_at)) {
            return view('doctor.pending-verification', compact('doctor'));
        }

        $active = $this->sessionService->getActiveForClinician((int) $doctor->user_id);
        $history = $this->sessionService->historyForClinician((int) $doctor->user_id);
        $accessLogs = $history['body']['data']['logs'] ?? [];

        $selectedPatient = null;
        $dashboardData = $this->formatDashboardFromTelemetry(null);
        $sessionId = null;

        if ($active) {
            $sessionId = $active['session_id'];
            $selectedPatient = [
                'id' => $active['child']['child_id'] ?? null,
                'name' => $active['child']['name'] ?? 'Unknown Patient',
                'guardian' => $active['child']['guardian'] ?? 'Guardian',
                'initials' => $active['child']['initials'] ?? 'PT',
                'birthdate' => $active['child']['birthdate'] ?? null,
                'patient_code' => $active['child']['patient_code'] ?? null,
                'last_sync' => $active['child']['last_sync'] ?? null,
            ];
            $dashboardData = $this->formatDashboardFromTelemetry($active['telemetry'] ?? null);
        }

        return view('doctor.dashboard', compact(
            'doctor',
            'active',
            'selectedPatient',
            'dashboardData',
            'sessionId',
            'accessLogs'
        ));
    }

    public function redeemAccess(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
        ]);

        $result = $this->sessionService->redeem((int) Auth::id(), $validated['code']);

        return response()->json($result['body'], $result['http_code']);
    }

    public function endSession(int $id)
    {
        $result = $this->sessionService->endByClinician((int) Auth::id(), $id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function activeSession()
    {
        $session = $this->sessionService->getActiveForClinician((int) Auth::id());

        return response()->json([
            'status' => 'success',
            'data' => [
                'active' => $session !== null,
                'session' => $session,
            ],
        ]);
    }

    public function accessLogs()
    {
        $result = $this->sessionService->historyForClinician((int) Auth::id());

        return response()->json($result['body'], $result['http_code']);
    }

    public function sessionTelemetry(int $id)
    {
        $result = $this->sessionService->telemetryForSession((int) Auth::id(), $id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function sessionReportPdf(int $id)
    {
        return $this->reportService->downloadPdf((int) Auth::id(), $id);
    }

    private function formatDashboardFromTelemetry(?array $telemetry): array
    {
        if (!$telemetry) {
            return [
                'health_grade' => 'No Data',
                'health_score' => null,
                'health_score_display' => '--',
                'latest_coins' => 0,
                'screen_time_display' => '--',
                'blink_rate_display' => '--',
                'distance_display' => '--',
                'target_days_display' => '0 / 0',
                'low_blink_events' => 0,
                'distance_violations' => 0,
                'labels' => [],
                'blink_rates' => [],
                'distances' => [],
                'screen_times' => [],
                'strain_events' => [],
                'health_scores' => [],
                'coins_data' => [],
                'activity_items' => [],
                'activity_page_items' => [],
                'activity_page' => 1,
                'activity_total_pages' => 1,
                'activity_total_items' => 0,
                'has_data' => false,
                'harmful_distance_cm' => 30,
            ];
        }

        $healthScore = $telemetry['health_score'] ?? null;

        return [
            'health_grade' => $telemetry['health_grade'] ?? 'No Data',
            'health_score' => $healthScore,
            'health_score_display' => $healthScore === null ? '--' : rtrim(rtrim(number_format((float) $healthScore, 1, '.', ''), '0'), '.') . '%',
            'latest_coins' => $telemetry['latest_coins'] ?? 0,
            'screen_time_display' => $this->formatDuration($telemetry['average_screen_time'] ?? null),
            'blink_rate_display' => $this->formatRate($telemetry['average_blink_rate'] ?? null),
            'distance_display' => $this->formatDistance($telemetry['average_distance'] ?? null),
            'target_days_display' => '--',
            'low_blink_events' => $telemetry['low_blink_events'] ?? 0,
            'distance_violations' => $telemetry['distance_violations'] ?? 0,
            'labels' => $telemetry['labels'] ?? [],
            'blink_rates' => $telemetry['blink_rates'] ?? [],
            'distances' => $telemetry['distances'] ?? [],
            'screen_times' => $telemetry['screen_times'] ?? [],
            'strain_events' => $telemetry['strain_events'] ?? [],
            'health_scores' => $telemetry['health_scores'] ?? [],
            'coins_data' => $telemetry['coins_data'] ?? [],
            'activity_items' => [],
            'activity_page_items' => [],
            'activity_page' => 1,
            'activity_total_pages' => 1,
            'activity_total_items' => 0,
            'has_data' => (bool) ($telemetry['has_data'] ?? false),
            'harmful_distance_cm' => $telemetry['harmful_distance_cm'] ?? 30,
        ];
    }

    private function formatDuration(?float $minutes): string
    {
        if ($minutes === null) {
            return '--';
        }
        $roundedMinutes = (int) round($minutes);
        $hours = intdiv($roundedMinutes, 60);
        $remainingMinutes = $roundedMinutes % 60;
        if ($hours > 0 && $remainingMinutes > 0) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        if ($hours > 0) {
            return $hours . 'h';
        }

        return $roundedMinutes . 'm';
    }

    private function formatRate(?float $value): string
    {
        if ($value === null) {
            return '--';
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . '/min';
    }

    private function formatDistance(?float $value): string
    {
        if ($value === null) {
            return '--';
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . 'cm';
    }
}
