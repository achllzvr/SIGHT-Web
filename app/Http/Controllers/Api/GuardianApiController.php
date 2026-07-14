<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegalDocumentService;
use App\Services\RuleEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GuardianApiController extends Controller
{
    public function __construct(
        private readonly RuleEngineService $ruleEngineService,
        private readonly LegalDocumentService $legalDocumentService,
    ) {
    }

    /**
     * POST /api/mobile/guardian/register
     */
    public function registerMobile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|string|min:6',
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer',
        ]);

        $payload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'contact_number' => null,
        ];

        $result = $this->ruleEngineService->registerGuardian($payload);

        if (($result['body']['status'] ?? '') === 'success') {
            $userId = (int) ($result['body']['data']['user']['id'] ?? 0);
            if ($userId > 0) {
                $this->legalDocumentService->accept($userId, $validated['document_ids'], $request->ip());
            }
        }

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/web/guardian/child/add
     * Creates a child_profile and generates a secure 6-digit login_code
     */
    public function addChild(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birthdate' => 'required|date|before:today',
        ]);

        $result = $this->ruleEngineService->addChild(
            (int) Auth::id(),
            $validated
        );

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * PUT /api/web/guardian/child/{child_id}/limits
     * Updates distance thresholds and screen time limits
     */
    public function updateChildLimits(Request $request, $child_id)
    {
        $validated = $request->validate([
            'daily_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'mode' => 'nullable|in:Strict,Relaxed',
            'harmful_distance_threshold' => 'nullable|numeric|min:1|max:100',
            'critical_distance_threshold' => 'nullable|numeric|min:1|max:100',
            'auto_enforce_breaks' => 'nullable|boolean',
        ]);

        $result = $this->ruleEngineService->updateChildLimits((int) $child_id, $validated);

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * DELETE /api/web/guardian/child/{child_id}
     * GDPR Compliance - cascade delete of child's data
     */
    public function deleteChild($child_id)
    {
        $result = $this->ruleEngineService->deleteChild((int) Auth::id(), (int) $child_id);

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/child/register
     */
    public function addChildMobile(Request $request)
    {
        $validated = $request->validate([
            'guardian_email' => 'required|email|exists:user,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'password' => 'required|string',
        ]);

        $guardianUser = User::where('email', $validated['guardian_email'])->first();
        
        $payload = [
            'first_name' => $validated['first_name'], 
            'last_name' => $validated['last_name'] ?? '', 
            'birthdate' => '2015-01-01', 
            'mobile_password' => $validated['password'],
        ];

        $result = $this->ruleEngineService->addChild((int) $guardianUser->user_id, $payload);
        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/guardian/verify-email
     * Simple email verification toggle for mobile
     */
    public function verifyEmailMobile(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:user,email']);
        
        $user = User::where('email', $request->email)->first();
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Email verified successfully.'
        ], 200);
    }

    /**
     * POST /api/mobile/guardian/reset-password
     * Mobile-specific password reset
     */
    public function resetPasswordMobile(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:user,email',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user->password_hash = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Password updated successfully.'
        ], 200);
    }

    /**
     * GET /api/mobile/guardian/children
     * Fetches live children for a specific guardian email
     */
    public function getChildrenMobile(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:user,email']);
        
        $guardianUser = User::where('email', $request->email)->first();
        $guardian = \App\Models\GuardianProfile::where('user_id', $guardianUser->user_id)->first();
        
        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found'], 404);
        }

        $children = \Illuminate\Support\Facades\DB::table('guardian_child_link')
            ->join('child_profile', 'guardian_child_link.child_id', '=', 'child_profile.child_id')
            ->join('user', 'child_profile.user_id', '=', 'user.user_id')
            ->where('guardian_child_link.guardian_id', $guardian->guardian_id)
            ->select(
                'child_profile.child_id', 
                'child_profile.login_code', 
                'child_profile.birthdate', 
                'user.first_name', 
                'user.last_name', 
                'user.password_hash' 
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $children
        ], 200);
    }
}
