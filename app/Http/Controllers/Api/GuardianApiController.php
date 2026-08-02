<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationOtpService;
use App\Services\GuardianChildAccess;
use App\Services\LegalDocumentService;
use App\Services\RuleEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GuardianApiController extends Controller
{
    public function __construct(
        private readonly RuleEngineService $ruleEngineService,
        private readonly LegalDocumentService $legalDocumentService,
        private readonly EmailVerificationOtpService $otpService,
        private readonly GuardianChildAccess $guardianChildAccess,
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
            'document_ids' => 'nullable|array',
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
                $documentIds = array_values(array_filter(
                    array_map('intval', $validated['document_ids'] ?? []),
                    fn (int $id) => $id > 0
                ));
                if (!empty($documentIds)) {
                    try {
                        $this->legalDocumentService->accept($userId, $documentIds, $request->ip());
                    } catch (\Throwable $e) {
                        logger()->warning('Legal acceptance skipped on register: '.$e->getMessage());
                    }
                }

                $user = User::find($userId);
                if ($user) {
                    try {
                        $this->otpService->createAndSend($user);
                    } catch (\Throwable $e) {
                        logger()->error('Failed to send verification OTP on register: ' . $e->getMessage());
                    }
                }
            }
        }

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/child/register
     */
    public function addChildMobile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'birthdate' => 'required|date|before:today',
            'password' => 'required|string',
        ]);

        $payload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
            'birthdate' => $validated['birthdate'],
            'mobile_password' => $validated['password'],
        ];

        $result = $this->ruleEngineService->addChild((int) Auth::id(), $payload);

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/guardian/verify-email
     */
    public function verifyEmailMobile(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:user,email',
            'otp' => 'required|string|size:6',
        ]);

        $result = $this->otpService->verify($validated['email'], $validated['otp']);

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/guardian/resend-verification
     */
    public function resendVerificationMobile(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:user,email',
        ]);

        $result = $this->otpService->resend($validated['email']);

        return response()->json($result['body'], $result['http_code']);
    }

    /**
     * POST /api/mobile/guardian/reset-password
     */
    public function resetPasswordMobile(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:user,email',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user->password_hash = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
        ], 200);
    }

    /**
     * PUT /api/mobile/child/{child_id}/password
     */
    public function updateChildPasswordMobile(Request $request, $child_id)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:4|max:255',
            'guardian_email' => 'required|email|exists:user,email',
            'guardian_password' => 'required|string',
        ]);

        $guardianUser = User::where('email', $validated['guardian_email'])->first();
        if (!$guardianUser || !Hash::check($validated['guardian_password'], $guardianUser->password_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid guardian credentials.',
            ], 401);
        }

        $child = \App\Models\ChildProfile::find($child_id);
        if (!$child) {
            return response()->json([
                'status' => 'error',
                'message' => 'Child not found.',
            ], 404);
        }

        if (!$this->guardianChildAccess->ownsChild((int) $guardianUser->user_id, (int) $child_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 403);
        }

        $childUser = $child->user;
        if (!$childUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Child user account not found.',
            ], 404);
        }

        $childUser->password_hash = Hash::make($validated['new_password']);
        $childUser->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Child password updated successfully.',
        ], 200);
    }

    /**
     * GET /api/mobile/guardian/children
     */
    public function getChildrenMobile()
    {
        $guardianUserId = (int) Auth::id();
        $guardian = \App\Models\GuardianProfile::where('user_id', $guardianUserId)->first();

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
                'user.last_name'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $children,
        ], 200);
    }
}
