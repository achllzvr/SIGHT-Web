<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ChildProfile;
use App\Models\DoctorProfile;
use App\Models\SessionLimits;
use App\Models\EyeHealthMetrics;
use App\Services\ClinicalAnalyticsService;
use App\Services\GuardianChildAccess;
use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SharedApiController extends Controller
{
    public function __construct(
        private readonly GuardianChildAccess $guardianChildAccess,
        private readonly UserPresenceService $presence,
        private readonly ClinicalAnalyticsService $analytics,
    ) {
    }

    /**
     * POST /api/shared/login
     * Authenticates Guardians and Admins via email/password
     * Enforces 3-Strike UDS (User Data Security) lockout rule
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->whereRaw('LOWER(role) IN (?, ?)', ['guardian', 'admin'])
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Active lock window — reject without consuming another attempt.
        if ($user->locked_until && now()->lt($user->locked_until)) {
            return response()->json([
                'message' => 'Account temporarily locked due to failed login attempts',
                'locked_until' => $user->locked_until,
            ], 429);
        }

        // Lock expired: clear counter so the user gets a fresh 3 attempts
        // (otherwise attempts stay >= 3 and one typo immediately re-locks).
        if ($user->locked_until && now()->gte($user->locked_until)) {
            $user->forceFill([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ])->save();
            $user->refresh();
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            $user->increment('failed_login_attempts');

            if ($user->failed_login_attempts >= 3) {
                $user->update(['locked_until' => now()->addMinutes(15)]);

                return response()->json([
                    'message' => 'Account locked due to 3 failed login attempts. Try again in 15 minutes.',
                ], 429);
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Guardians must complete email OTP before mobile API access.
        if (strtolower((string) $user->role) === 'guardian' && is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Verify your email before signing in.',
                'email_verified' => false,
            ], 403);
        }

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->presence->touch($user);

        // Rotate only the mobile bearer token. Do not revoke other Sanctum tokens
        // (e.g. future web API tokens) so concurrent mobile + web sessions coexist.
        $user->tokens()->where('name', 'guardian-mobile')->delete();
        $token = $user->createToken('guardian-mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->user_id,
                'email' => $user->email,
                'role' => $user->role,
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                'display_name' => $user->display_name,
                'email_verified' => !is_null($user->email_verified_at),
            ],
            'token' => $token,
        ], 200);
    }

    /**
     * POST /api/shared/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * GET /api/shared/child/{child_id}/limits
     */
    public function getChildLimits($child_id)
    {
        if ($response = $this->authorizeChildAccess((int) $child_id)) {
            return $response;
        }

        $limits = SessionLimits::where('child_id', $child_id)->first();

        if (!$limits) {
            return response()->json(['message' => 'Limits not found'], 404);
        }

        return response()->json([
            'limit_id' => $limits->limit_id,
            'child_id' => $limits->child_id,
            'daily_limit_minutes' => $limits->daily_limit_minutes,
            'mode' => $limits->mode,
            'is_active' => (bool) $limits->is_active,
            'harmful_distance_threshold' => $limits->harmful_distance_threshold,
            'critical_distance_threshold' => $limits->critical_distance_threshold,
            'auto_enforce_breaks' => (bool) $limits->auto_enforce_breaks,
            'updated_at' => $limits->updated_at->toIso8601String(),
        ], 200);
    }

    /**
     * GET /api/shared/child/{child_id}/metrics
     */
    public function getChildMetrics(Request $request, $child_id)
    {
        if ($response = $this->authorizeChildAccess((int) $child_id)) {
            return $response;
        }

        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after:from_date',
            'range' => 'nullable|string|in:7d,30d,90d,180d,365d,lifetime',
        ]);

        $query = EyeHealthMetrics::where('child_id', $child_id);

        if ($request->filled('range')) {
            $resolved = $this->analytics->resolveDateRange($request->string('range')->toString());
            $query->whereBetween('timestamp', [$resolved['start'], $resolved['end']]);
        } else {
            if ($request->has('from_date')) {
                $query->whereDate('timestamp', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('timestamp', '<=', $request->to_date);
            }
        }

        $perPage = $request->input('per_page', 50);
        $metrics = $query->orderBy('timestamp', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $metrics->items(),
            'pagination' => [
                'current_page' => $metrics->currentPage(),
                'total_pages' => $metrics->lastPage(),
                'total_records' => $metrics->total(),
                'per_page' => $metrics->perPage(),
            ],
        ], 200);
    }

    /**
     * GET /api/shared/child/{child_id}/dashboard?range=7d
     * Multi-horizon analytics + OPI + usage tiers + Part-4 clinical trend alert.
     */
    public function getChildDashboard(Request $request, $child_id)
    {
        if ($response = $this->authorizeChildAccess((int) $child_id)) {
            return $response;
        }

        $request->validate([
            'range' => 'nullable|string|in:7d,30d,90d,180d,365d,lifetime',
        ]);

        $child = ChildProfile::find($child_id);
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        $range = $request->input('range', '7d');
        $dashboard = $this->analytics->getChildDashboardMetrics($child, $range);
        $childUser = User::find($child->user_id);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($dashboard, [
                'presence' => $childUser ? $this->presence->summarize($childUser) : [
                    'presence_status' => 'offline',
                    'presence_label' => 'Offline',
                    'last_active' => 'Never',
                ],
                'last_sync' => optional($child->last_sync)?->toIso8601String(),
            ]),
        ], 200);
    }

    /**
     * GET /api/shared/child/{child_id}/presence — lightweight 15s poll target.
     */
    public function getChildPresence($child_id)
    {
        if ($response = $this->authorizeChildAccess((int) $child_id)) {
            return $response;
        }

        $child = ChildProfile::find($child_id);
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        $childUser = User::find($child->user_id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'child_id' => (int) $child_id,
                'last_sync' => optional($child->last_sync)?->toIso8601String(),
                'presence' => $childUser ? $this->presence->summarize($childUser) : [
                    'presence_status' => 'offline',
                    'presence_label' => 'Offline',
                    'last_active' => 'Never',
                ],
            ],
        ], 200);
    }

    /**
     * GET /api/shared/partnered-professionals
     */
    public function listPartneredProfessionals(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'specialty' => 'nullable|string|max:255',
            'q' => 'nullable|string|max:255',
        ]);

        $query = DoctorProfile::query()
            ->with('user')
            ->where('is_validated', true)
            ->where(function ($q) {
                $q->where('is_partnered_directory_visible', true)
                    ->orWhereNull('is_partnered_directory_visible');
            })
            ->whereHas('user', function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['active']);
            });

        if ($request->filled('specialty')) {
            $query->where('specialty', 'like', '%' . $request->string('specialty')->toString() . '%');
        }
        if ($request->filled('q')) {
            $term = '%' . $request->string('q')->toString() . '%';
            $query->where(function ($q) use ($term) {
                $q->where('clinic', 'like', $term)
                    ->orWhere('clinic_address', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    });
            });
        }

        $rows = $query->get()->map(function (DoctorProfile $profile) {
            $u = $profile->user;
            $presence = $u ? $this->presence->summarize($u) : [
                'presence_status' => 'offline',
                'presence_label' => 'Offline',
            ];

            return [
                'doctor_id' => $profile->doctor_id,
                'full_name' => $u?->display_name ?? 'Clinician',
                'specialty' => $profile->specialty,
                'clinic_name' => $profile->clinic,
                'clinic_address' => $profile->clinic_address ?? $profile->location,
                'operating_hours' => $profile->operating_hours,
                'phone' => $profile->phone,
                'email' => $u?->email,
                'prc_license' => $profile->license_number,
                'latitude' => $profile->latitude !== null ? (float) $profile->latitude : null,
                'longitude' => $profile->longitude !== null ? (float) $profile->longitude : null,
                'presence_status' => $presence['presence_status'],
                'presence_label' => $presence['presence_label'],
                'is_lumi_verified' => (bool) $profile->is_validated,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => ['professionals' => $rows],
        ], 200);
    }

    private function authorizeChildAccess(int $childId): ?\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $role = strtolower((string) $user->role);

        if ($role === 'admin') {
            return null;
        }

        if ($role === 'guardian') {
            if ($this->guardianChildAccess->ownsChild((int) $user->user_id, $childId)) {
                return null;
            }

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
