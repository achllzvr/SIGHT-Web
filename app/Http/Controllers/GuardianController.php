<?php

namespace App\Http\Controllers;

use App\Models\ChildProfile;
use App\Models\DoctorProfile;
use App\Models\GuardianProfile;
use App\Models\User;
use App\Services\ClinicalAnalyticsService;
use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    public function __construct(
        private readonly ClinicalAnalyticsService $analytics,
        private readonly UserPresenceService $presence,
    ) {
    }

    /**
     * Legacy redirect target — kept for bookmarks; prefer dashboard().
     */
    public function useMobile()
    {
        return redirect()->route('guardian.dashboard');
    }

    /**
     * Read-only Guardian Web Dashboard (concurrent with mobile Watch Area).
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $guardian = GuardianProfile::where('user_id', $user->user_id)->first();

        $children = collect();
        if ($guardian) {
            $childIds = DB::table('guardian_child_link')
                ->where('guardian_id', $guardian->guardian_id)
                ->pluck('child_id');
            $children = ChildProfile::with('user')
                ->whereIn('child_id', $childIds)
                ->get();
        }

        $range = $request->input('range', '7d');
        if (!in_array($range, ClinicalAnalyticsService::RANGES, true)) {
            $range = '7d';
        }

        $selectedId = (int) $request->input('child_id', $children->first()?->child_id ?? 0);
        $selected = $children->firstWhere('child_id', $selectedId) ?? $children->first();

        $dashboard = null;
        $presence = null;
        if ($selected) {
            $dashboard = $this->analytics->getChildDashboardMetrics($selected, $range);
            $childUser = User::find($selected->user_id);
            $presence = $childUser ? $this->presence->summarize($childUser) : [
                'presence_status' => 'offline',
                'presence_label' => 'Offline',
                'last_active' => 'Never',
            ];
        }

        $professionals = DoctorProfile::query()
            ->with('user')
            ->where('is_validated', true)
            ->where(function ($q) {
                $q->where('is_partnered_directory_visible', true)
                    ->orWhereNull('is_partnered_directory_visible');
            })
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(status) = ?', ['active']))
            ->limit(24)
            ->get();

        return view('guardian.dashboard', compact(
            'user',
            'children',
            'selected',
            'range',
            'dashboard',
            'presence',
            'professionals',
        ));
    }

    /**
     * Lightweight JSON poll for presence + summary KPIs.
     */
    public function poll(Request $request)
    {
        $request->validate([
            'child_id' => 'required|integer',
            'range' => 'nullable|string|in:7d,30d,90d,180d,365d,lifetime',
        ]);

        $user = Auth::user();
        $guardian = GuardianProfile::where('user_id', $user->user_id)->first();
        if (!$guardian) {
            return response()->json(['message' => 'Guardian profile not found'], 404);
        }

        $owns = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $request->integer('child_id'))
            ->exists();
        if (!$owns) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $child = ChildProfile::find($request->integer('child_id'));
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        $range = $request->input('range', '7d');
        $dashboard = $this->analytics->getChildDashboardMetrics($child, $range);
        $childUser = User::find($child->user_id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'presence' => $childUser ? $this->presence->summarize($childUser) : [
                    'presence_status' => 'offline',
                    'presence_label' => 'Offline',
                ],
                'clinical_trend_alert' => $dashboard['clinical_trend_alert'] ?? null,
                'usage_threshold' => $dashboard['usage_threshold'] ?? null,
                'health_grade' => $dashboard['health_grade'] ?? null,
                'mean_distance_cm' => $dashboard['mean_distance_cm'] ?? null,
                'mean_blink_rate' => $dashboard['mean_blink_rate'] ?? null,
                'mean_screen_time_minutes' => $dashboard['mean_screen_time_minutes'] ?? null,
                'last_sync' => optional($child->last_sync)?->toIso8601String(),
            ],
        ]);
    }

    public function children()
    {
        return redirect()->route('guardian.dashboard');
    }
}
