<?php

namespace App\Services;

use App\Models\PatientAccessLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ClinicianTelemetryReportService
{
    public function __construct(
        private readonly PatientAccessSessionService $sessionService,
    ) {
    }

    public function downloadPdf(int $clinicianUserId, int $sessionId): Response
    {
        $session = $this->sessionService->assertActive($sessionId, $clinicianUserId);
        $session->loadMissing(['child.user', 'clinician']);
        $telemetry = $this->sessionService->buildTelemetry($session->child);

        $pdf = Pdf::loadView('reports.patient-telemetry', [
            'session' => $session,
            'child' => $session->child,
            'clinician' => $session->clinician,
            'telemetry' => $telemetry,
            'generatedAt' => now(),
        ]);

        $filename = 'sight_telemetry_session_' . $session->id . '.pdf';

        return $pdf->download($filename);
    }
}
