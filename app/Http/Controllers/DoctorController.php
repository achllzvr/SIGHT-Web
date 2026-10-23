<?php

namespace App\Http\Controllers;

use App\Models\DoctorProfile;
use App\Rules\StrongPassword;
use App\Services\ClinicianTelemetryReportService;
use App\Services\PatientAccessSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

        $doctor->loadMissing('doctorProfile');
        $doctorProfile = $doctor->doctorProfile;

        $active = $this->sessionService->getActiveForClinician((int) $doctor->user_id);
        $history = $this->sessionService->historyForClinician((int) $doctor->user_id);
        $accessLogs = $history['body']['data']['logs'] ?? [];

        $selectedPatient = null;
        $dashboardData = $this->formatDashboardFromTelemetry(null);
        $sessionId = null;
        $range = $request->input('range', '7d');
        if (!in_array($range, ['7d', '30d', '90d', '180d', '365d', 'lifetime'], true)) {
            $range = '7d';
        }

        $openSettings = $request->boolean('settings')
            || $request->session()->has('success')
            || ($request->session()->get('errors') instanceof \Illuminate\Support\ViewErrorBag
                && $request->session()->get('errors')->any());

        if ($request->session()->getOldInput()) {
            $openSettings = $openSettings || collect($request->session()->getOldInput())->keys()->intersect([
                'first_name', 'last_name', 'phone', 'clinic', 'specialty', 'location', 'license_number',
                'current_password', 'new_password', 'new_password_confirmation',
            ])->isNotEmpty();
        }

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
            // Rebuild telemetry for selected multi-horizon range when child is available.
            $childId = $selectedPatient['id'] ?? null;
            if ($childId) {
                $child = \App\Models\ChildProfile::find($childId);
                $telemetry = $child
                    ? $this->sessionService->buildTelemetry($child, $range)
                    : ($active['telemetry'] ?? null);
                $dashboardData = $this->formatDashboardFromTelemetry($telemetry);
            } else {
                $dashboardData = $this->formatDashboardFromTelemetry($active['telemetry'] ?? null);
            }
        }

        return view('doctor.dashboard', compact(
            'doctor',
            'doctorProfile',
            'active',
            'selectedPatient',
            'dashboardData',
            'sessionId',
            'accessLogs',
            'openSettings',
            'range'
        ));
    }

    /**
     * Update the signed-in doctor's account & professional profile.
     * Mirrors parent Account & Security (password) plus editable profile fields from signup.
     */
    public function updateSettings(Request $request)
    {
        $doctor = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'clinic' => 'required|string|max:255',
            'specialty' => 'required|string|max:255',
            'license_number' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:500',
            'operating_hours' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_partnered_directory_visible' => 'nullable|boolean',
            'current_password' => 'required_with:new_password|nullable|string',
            'new_password' => ['nullable', 'string', 'confirmed', new StrongPassword()],
        ]);

        if ($request->filled('new_password')) {
            if (!Hash::check((string) $request->input('current_password'), $doctor->password_hash)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The provided password does not match your current password.'],
                ]);
            }
            $doctor->password_hash = Hash::make($validated['new_password']);
        }

        if (Schema::hasColumn('user', 'first_name')) {
            $doctor->first_name = $validated['first_name'];
        }
        if (Schema::hasColumn('user', 'last_name')) {
            $doctor->last_name = $validated['last_name'];
        }

        foreach (['phone', 'clinic', 'specialty', 'license_number', 'location'] as $column) {
            if (Schema::hasColumn('user', $column) && array_key_exists($column, $validated)) {
                $doctor->{$column} = $validated[$column] ?? '';
            }
        }

        $doctor->save();

        if (Schema::hasTable('doctor_profile')) {
            $profile = DoctorProfile::where('user_id', $doctor->user_id)->first();
            $profilePayload = $this->doctorProfilePayload([
                'phone' => $validated['phone'],
                'clinic' => $validated['clinic'],
                'specialty' => $validated['specialty'],
                'license_number' => $validated['license_number'],
                'location' => $validated['location'] ?? '',
                'clinic_address' => $validated['clinic_address'] ?? ($validated['location'] ?? ''),
                'operating_hours' => $validated['operating_hours'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'is_partnered_directory_visible' => $request->boolean('is_partnered_directory_visible', true),
            ]);

            if ($profile) {
                $profile->fill($profilePayload);
                $profile->save();
            } else {
                $nextId = ((int) DB::table('doctor_profile')->max('doctor_id')) + 1;
                $createPayload = array_merge($profilePayload, [
                    'user_id' => $doctor->user_id,
                ]);
                if (Schema::hasColumn('doctor_profile', 'doctor_id')) {
                    $createPayload['doctor_id'] = $nextId;
                }
                if (Schema::hasColumn('doctor_profile', 'is_validated')) {
                    $createPayload['is_validated'] = 0;
                }
                DoctorProfile::create($createPayload);
            }
        }

        return redirect()
            ->route('doctor.dashboard', ['settings' => 1])
            ->with('success', 'Account settings updated successfully.');
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

    public function sessionTelemetry(Request $request, int $id)
    {
        $request->validate([
            'range' => 'nullable|string|in:7d,30d,90d,180d,365d,lifetime',
        ]);
        $range = $request->input('range', '7d');
        $result = $this->sessionService->telemetryForSession((int) Auth::id(), $id, $range);

        return response()->json($result['body'], $result['http_code']);
    }

    public function sessionReportPdf(Request $request, int $id)
    {
        $range = $request->input('range', '7d');
        if (!in_array($range, ['7d', '30d', '90d', '180d', '365d', 'lifetime'], true)) {
            $range = '7d';
        }

        return $this->reportService->downloadPdf((int) Auth::id(), $id, $range);
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
                'clinical_trend_alert' => null,
                'usage_threshold' => null,
                'ocular_protection_index' => null,
                'opi_display' => '--',
                'diopters_display' => '--',
                'presence' => null,
                'range' => '7d',
                'range_label' => 'Last 7 Days',
            ];
        }

        $healthScore = $telemetry['health_score'] ?? null;
        $opi = $telemetry['ocular_protection_index'] ?? null;
        $opiDisplay = '--';
        if (is_array($opi) && ($opi['opi'] ?? null) !== null) {
            $opiDisplay = number_format((float) $opi['opi'], 2) . ' · Level ' . ($opi['level'] ?? '—');
        }
        $diopters = $telemetry['mean_diopters'] ?? null;

        return [
            'health_grade' => $telemetry['health_grade'] ?? 'No Data',
            'health_score' => $healthScore,
            'health_score_display' => $healthScore === null ? '--' : rtrim(rtrim(number_format((float) $healthScore, 1, '.', ''), '0'), '.') . '%',
            'latest_coins' => $telemetry['latest_coins'] ?? 0,
            'screen_time_display' => $this->formatDuration($telemetry['average_screen_time'] ?? null),
            'blink_rate_display' => $this->formatRate($telemetry['average_blink_rate'] ?? null),
            'distance_display' => $this->formatDistance($telemetry['average_distance'] ?? null),
            'opi_display' => $opiDisplay,
            'diopters_display' => $diopters === null ? '--' : number_format((float) $diopters, 2) . ' D',
            'target_days_display' => '--',
            'low_blink_events' => $telemetry['low_blink_events'] ?? 0,
            'distance_violations' => $telemetry['distance_violations'] ?? 0,
            'clinical_trend_alert' => $telemetry['clinical_trend_alert'] ?? null,
            'usage_threshold' => $telemetry['usage_threshold'] ?? null,
            'ocular_protection_index' => $telemetry['ocular_protection_index'] ?? null,
            'presence' => $telemetry['presence'] ?? null,
            'range' => $telemetry['range'] ?? '7d',
            'range_label' => $telemetry['range_label'] ?? 'Last 7 Days',
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

    /**
     * Only include doctor_profile columns that exist (Hostinger may lag migrations).
     */
    private function doctorProfilePayload(array $input): array
    {
        $payload = [];
        foreach ($input as $column => $value) {
            if (Schema::hasColumn('doctor_profile', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }
}
