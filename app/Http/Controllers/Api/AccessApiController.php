<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\ClinicianTelemetryReportService;
use App\Services\LegalDocumentService;
use App\Services\PatientAccessSessionService;
use App\Services\TemporaryAccessTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessApiController extends Controller
{
    public function __construct(
        private readonly TemporaryAccessTokenService $tokenService,
        private readonly PatientAccessSessionService $sessionService,
        private readonly LegalDocumentService $legalService,
        private readonly AuditLogService $auditLogService,
        private readonly ClinicianTelemetryReportService $reportService,
    ) {
    }

    public function generateToken(Request $request, int $child_id)
    {
        $result = $this->tokenService->generateForChild((int) Auth::id(), $child_id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function childActiveSession(int $child_id)
    {
        $session = $this->sessionService->getActiveForChild((int) Auth::id(), $child_id);

        return response()->json([
            'status' => 'success',
            'message' => $session ? 'Active session found' : 'No active session',
            'data' => [
                'active' => $session !== null,
                'session' => $session,
            ],
            'errors' => null,
        ]);
    }

    public function endSessionMobile(int $id)
    {
        $result = $this->sessionService->endByGuardian((int) Auth::id(), $id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function childAccessLogs(int $child_id)
    {
        $result = $this->sessionService->historyForChild((int) Auth::id(), $child_id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function guardianAccessLogs()
    {
        $result = $this->sessionService->historyForGuardian((int) Auth::id());

        return response()->json($result['body'], $result['http_code']);
    }

    public function ingestActivity(Request $request)
    {
        $validated = $request->validate([
            'event_tag' => 'required|string|max:255',
            'child_id' => 'nullable|integer',
            'old_value' => 'nullable',
            'new_value' => 'nullable',
            'occurred_at' => 'nullable|date',
        ]);

        $result = $this->auditLogService->ingestMobileActivity(
            (int) Auth::id(),
            $validated,
            $request->ip()
        );

        return response()->json($result['body'], $result['http_code']);
    }

    public function latestLegalDocuments()
    {
        try {
            $result = $this->legalService->latestDocuments();

            return response()->json($result['body'], $result['http_code']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to load legal documents right now.',
                'data' => ['documents' => []],
                'errors' => ['legal' => ['Legal documents temporarily unavailable']],
            ], 503);
        }
    }

    public function acceptLegalDocuments(Request $request)
    {
        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer',
        ]);

        $userId = (int) Auth::id();
        if ($userId <= 0) {
            // Allow pre-auth acceptance with pending registration flow via guest header not used;
            // mobile should register then accept, or accept after creating user.
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required',
                'data' => new \stdClass(),
                'errors' => ['auth' => ['Authentication required']],
            ], 401);
        }

        $result = $this->legalService->accept($userId, $validated['document_ids'], $request->ip());

        return response()->json($result['body'], $result['http_code']);
    }

    public function redeem(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
        ]);

        $result = $this->sessionService->redeem((int) Auth::id(), $validated['code']);

        return response()->json($result['body'], $result['http_code']);
    }

    public function clinicianActiveSession()
    {
        $session = $this->sessionService->getActiveForClinician((int) Auth::id());

        return response()->json([
            'status' => 'success',
            'message' => $session ? 'Active session found' : 'No active session',
            'data' => [
                'active' => $session !== null,
                'session' => $session,
            ],
            'errors' => null,
        ]);
    }

    public function endSessionClinician(int $id)
    {
        $result = $this->sessionService->endByClinician((int) Auth::id(), $id);

        return response()->json($result['body'], $result['http_code']);
    }

    public function clinicianAccessLogs()
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
}
