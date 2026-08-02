<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SessionLimits;
use App\Models\EyeHealthMetrics;
use App\Services\GuardianChildAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SharedApiController extends Controller
{
    public function __construct(
        private readonly GuardianChildAccess $guardianChildAccess,
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

        if ($user->locked_until && now() < $user->locked_until) {
            return response()->json([
                'message' => 'Account temporarily locked due to failed login attempts',
                'locked_until' => $user->locked_until,
            ], 429);
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

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

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
        ]);

        $query = EyeHealthMetrics::where('child_id', $child_id);

        if ($request->has('from_date')) {
            $query->whereDate('timestamp', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('timestamp', '<=', $request->to_date);
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
